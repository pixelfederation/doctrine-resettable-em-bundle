<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DependencyInjection\CompilerPass;

use PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper\AggregatedAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper\OptimizedAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\DBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\FailoverAware\FailoverAwareAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\PassiveIgnoringDBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\TransactionDiscardingDBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DependencyInjection\Parameters;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\PassiveIgnoringRedisClusterAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\RedisClusterAliveKeeper;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class AliveKeeperPass implements CompilerPassInterface
{
    public const FAILOVER_CONNECTIONS_PARAM_NAME = 'pixelfederation_doctrine_resettable_em_bundle.failover_connections';
    public const REDIS_CLUSTER_CONNECTIONS_PARAM_NAME =
        'pixelfederation_doctrine_resettable_em_bundle.redis_cluster_connections';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter(self::FAILOVER_CONNECTIONS_PARAM_NAME)) {
            return;
        }

        $aliveKeepers = $this->createAliveKeepers($container);

        $aggregatedAliveKeeper = $container->findDefinition(AggregatedAliveKeeper::class);
        $aggregatedAliveKeeper->setArgument('$aliveKeepers', $aliveKeepers);
    }

    /**
     * @return array<ChildDefinition|Reference>
     */
    private function createAliveKeepers(ContainerBuilder $container): array
    {
        return array_merge(
            $this->createDoctrineAliveKeepers($container),
            $this->createRedisClusterAliveKeepers($container)
        );
    }

    /**
     * @return array<Reference>
     */
    private function createDoctrineAliveKeepers(ContainerBuilder $container): array
    {
        /** @var array<string, string> $connections */
        $connections = $container->getParameter('doctrine.connections');
        /** @var array<string, string> $failoverConnections */
        $failoverConnections = $container->getParameter(self::FAILOVER_CONNECTIONS_PARAM_NAME);
        $pingInterval = $container->hasParameter(Parameters::PING_INTERVAL) ?
            $container->getParameter(Parameters::PING_INTERVAL) : 0;
        $aliveKeepers = [];

        foreach ($connections as $connectionName => $connectionSvcId) {
            $aliveKeeperSvcId = sprintf('pixel_federation_doctrine_resettable_em.alive_keeper.%s', $connectionName);
            $aliveKeeper = $container->setDefinition(
                $aliveKeeperSvcId,
                $this->getAliveKeeperDefinition($connectionName, $failoverConnections)
            );
            $aliveKeeper->setArgument('$connection', new Reference($connectionSvcId));

            $decoratorAliveKeeperSvcId = sprintf('%s_%s', TransactionDiscardingDBALAliveKeeper::class, $connectionName);
            $decoratedAliveKeeperSvcId = sprintf('%s.inner', $decoratorAliveKeeperSvcId);
            $transDiscardingDef = new ChildDefinition(TransactionDiscardingDBALAliveKeeper::class);
            $transDiscardingDef->setDecoratedService($aliveKeeperSvcId, $decoratedAliveKeeperSvcId, 1);
            $transDiscardingDef->setArgument('$decorated', new Reference($decoratedAliveKeeperSvcId));
            $transDiscardingDef->setArgument('$connection', new Reference($connectionSvcId));
            $transDiscardingDef->setArgument('$connectionName', $connectionName);
            $container->setDefinition($decoratorAliveKeeperSvcId, $transDiscardingDef);

            $passiveDecoratorAliveKeeperSvcId = sprintf(
                '%s_%s',
                PassiveIgnoringDBALAliveKeeper::class,
                $connectionName
            );

            $ignorePassiveAliveKeeperSvcId = sprintf('%s.inner', $passiveDecoratorAliveKeeperSvcId);
            $passiveIgnoringDef = new ChildDefinition(PassiveIgnoringDBALAliveKeeper::class);
            $passiveIgnoringDef->setDecoratedService($aliveKeeperSvcId, $ignorePassiveAliveKeeperSvcId);
            $passiveIgnoringDef->setArgument('$decorated', new Reference($ignorePassiveAliveKeeperSvcId));
            $passiveIgnoringDef->setArgument('$connection', new Reference($connectionSvcId));
            $container->setDefinition($passiveDecoratorAliveKeeperSvcId, $passiveIgnoringDef);

            $aliveKeepers[] = new Reference($aliveKeeperSvcId);

            if ($pingInterval === 0) {
                continue;
            }

            $optDecoratorAliveKeeperSvcId = sprintf('%s:doctrine_%s', OptimizedAliveKeeper::class, $connectionName);
            $optDecoratedAliveKeeperSvcId = sprintf('%s.inner', $optDecoratorAliveKeeperSvcId);
            $optimisedKeeperDef = new ChildDefinition(OptimizedAliveKeeper::class);
            $optimisedKeeperDef->setDecoratedService($aliveKeeperSvcId, $optDecoratedAliveKeeperSvcId, 2);
            $optimisedKeeperDef->setArgument('$decorated', new Reference($optDecoratedAliveKeeperSvcId));
            $optimisedKeeperDef->setArgument('$pingIntervalInSeconds', $pingInterval);
            $container->setDefinition($optDecoratorAliveKeeperSvcId, $optimisedKeeperDef);
        }

        return $aliveKeepers;
    }

    /**
     * @param array<string, string> $failoverConnections
     */
    private function getAliveKeeperDefinition(string $connectionName, array $failoverConnections): ChildDefinition
    {
        if (!isset($failoverConnections[$connectionName])) {
            return new ChildDefinition(DBALAliveKeeper::class);
        }

        $aliveKeeper = new ChildDefinition(FailoverAwareAliveKeeper::class);
        $aliveKeeper->setArgument('$connectionName', $connectionName);
        $aliveKeeper->setArgument('$connectionType', $failoverConnections[$connectionName]);

        return $aliveKeeper;
    }

    /**
     * @return array<ChildDefinition>
     */
    private function createRedisClusterAliveKeepers(ContainerBuilder $container): array
    {
        /** @var array<string, string> $clusterConnections */
        $clusterConnections = $container->getParameter(self::REDIS_CLUSTER_CONNECTIONS_PARAM_NAME);
        $pingInterval = $container->hasParameter(Parameters::PING_INTERVAL) ?
            $container->getParameter(Parameters::PING_INTERVAL) : 0;
        $aliveKeepers = [];

        foreach ($clusterConnections as $connectionName => $clusterSvcId) {
            $clusterDef = $container->findDefinition($clusterSvcId);
            $aliveKeeper = new ChildDefinition(RedisClusterAliveKeeper::class);
            $aliveKeeper->setArgument('$connectionName', $connectionName);
            $aliveKeeper->setArgument('$redis', new Reference($clusterSvcId));
            $aliveKeeper->setArgument('$constructorArguments', array_values($clusterDef->getArguments()));
            $aliveKeeperSvcId = sprintf(
                'pixel_federation_doctrine_resettable_em.alive_keeper.redis.%s',
                $connectionName
            );
            $container->setDefinition($aliveKeeperSvcId, $aliveKeeper);

            $passiveDecoratorAliveKeeperSvcId = sprintf(
                '%s_%s',
                PassiveIgnoringRedisClusterAliveKeeper::class,
                $connectionName
            );

            $ignorePassiveAliveKeeperSvcId = sprintf('%s.inner', $passiveDecoratorAliveKeeperSvcId);
            $passiveIgnoringDef = new ChildDefinition(PassiveIgnoringRedisClusterAliveKeeper::class);
            $passiveIgnoringDef->setDecoratedService($aliveKeeperSvcId, $ignorePassiveAliveKeeperSvcId);
            $passiveIgnoringDef->setArgument('$decorated', new Reference($ignorePassiveAliveKeeperSvcId));
            $passiveIgnoringDef->setArgument('$redis', new Reference($clusterSvcId));
            $container->setDefinition($passiveDecoratorAliveKeeperSvcId, $passiveIgnoringDef);

            $aliveKeepers[] = new Reference($aliveKeeperSvcId);

            if ($pingInterval === 0) {
                continue;
            }

            $optDecoratorAliveKeeperSvcId = sprintf('%s:redis_%s', OptimizedAliveKeeper::class, $connectionName);
            $optDecoratedAliveKeeperSvcId = sprintf('%s.inner', $optDecoratorAliveKeeperSvcId);
            $optimisedKeeperDef = new ChildDefinition(OptimizedAliveKeeper::class);
            $optimisedKeeperDef->setDecoratedService($aliveKeeperSvcId, $optDecoratedAliveKeeperSvcId, 2);
            $optimisedKeeperDef->setArgument('$decorated', new Reference($optDecoratedAliveKeeperSvcId));
            $optimisedKeeperDef->setArgument('$pingIntervalInSeconds', $pingInterval);
            $container->setDefinition($optDecoratorAliveKeeperSvcId, $optimisedKeeperDef);
        }

        return $aliveKeepers;
    }
}
