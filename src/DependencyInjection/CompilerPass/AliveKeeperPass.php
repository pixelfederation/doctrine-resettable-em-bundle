<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DependencyInjection\CompilerPass;

use PixelFederation\DoctrineResettableEmBundle\Connection\ConnectionsHandler;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\DBALPlatformAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\FailoverAware\FailoverAwareDBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\OptimizedDBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\PassiveIgnoringDBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\PingingDBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\TransactionDiscardingDBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DependencyInjection\Parameters;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\OptimizedRedisClusterAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\PassiveIgnoringRedisClusterAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\PingingRedisClusterAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\RedisClusterPlatformAliveKeeper;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

//phpcs:disable
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

        $aliveKeepers = $this->createPlatformAliveKeepers($container);

        $connectionsHandlerDef = $container->findDefinition(ConnectionsHandler::class);
        $connectionsHandlerDef->setArgument('$aliveKeepers', $aliveKeepers);
        $connectionsHandlerDef->addTag('pixelfederation_doctrine_resettable_em_bundle.app_initializer');
    }

    /**
     * @return array<ChildDefinition|Reference>
     */
    private function createPlatformAliveKeepers(ContainerBuilder $container): array
    {
        $aliveKeepers = [];
        $dbalAliveKeeper = $this->createDBALPlatformAliveKeeper($container);

        if ($dbalAliveKeeper !== null) {
            $aliveKeepers[] = $dbalAliveKeeper;
        }

        $redisClusterAliveKeeper = $this->createRedisClusterPlatformAliveKeeper($container);

        if ($redisClusterAliveKeeper !== null) {
            $aliveKeepers[] = $redisClusterAliveKeeper;
        }

        return $aliveKeepers;
    }

    private function createDBALPlatformAliveKeeper(ContainerBuilder $container): ?Reference
    {
        $aliveKeepers = $this->createDBALAliveKeepers($container);

        if (count($aliveKeepers) === 0) {
            return null;
        }

        // @var array<string, string> $connections
        $connections = $container->getParameter('doctrine.connections');
        $connectionRefs = [];

        foreach ($connections as $connectionName => $connectionSvcId) {
            $connectionRefs[$connectionName] = new Reference($connectionSvcId);
        }

        $aliveKeeperDef = $container->findDefinition(DBALPlatformAliveKeeper::class);
        $aliveKeeperDef->setArgument('$connections', $connectionRefs);
        $aliveKeeperDef->setArgument('$aliveKeepers', $aliveKeepers);

        return new Reference(DBALPlatformAliveKeeper::class);
    }

    private function createRedisClusterPlatformAliveKeeper(ContainerBuilder $container): ?Reference
    {
        $connections = $this->createRedisClusterConnectionReferences($container);

        if (count($connections) === 0) {
            return null;
        }

        $aliveKeepers = $this->createRedisClusterAliveKeepers($container);

        if (count($aliveKeepers) === 0) {
            return null;
        }

        $aliveKeeperDef = $container->findDefinition(RedisClusterPlatformAliveKeeper::class);
        $aliveKeeperDef->setArgument('$connections', $connections);
        $aliveKeeperDef->setArgument('$aliveKeepers', $aliveKeepers);

        return new Reference(RedisClusterPlatformAliveKeeper::class);
    }

    /**
     * @return array<Reference>
     */
    private function createDBALAliveKeepers(ContainerBuilder $container): array
    {
        // @var array<string, string> $connections
        $connections = $container->getParameter('doctrine.connections');
        // @var array<string, string> $failoverConnections

        $failoverConnections = $container->getParameter(self::FAILOVER_CONNECTIONS_PARAM_NAME);
        $pingInterval = (int) $container->hasParameter(Parameters::PING_INTERVAL)
            ? $container->getParameter(Parameters::PING_INTERVAL)
            : 0;
        $checkActiveTransactions = (bool) $container->hasParameter(Parameters::CHECK_ACTIVE_TRANSACTIONS)
            ? $container->getParameter(Parameters::CHECK_ACTIVE_TRANSACTIONS)
            : false;
        // @var array<string> $excluded

        $excluded = $container->getParameter(Parameters::EXCLUDED_FROM_PROCESSING_DBAL_CONNECTIONS);
        $aliveKeepers = [];

        foreach (array_keys($connections) as $connectionName) {
            if (in_array($connectionName, $excluded, true)) {
                continue;
            }

            $aliveKeeperSvcId = sprintf(
                'pixel_federation_doctrine_resettable_em.alive_keeper.dbal.%s',
                $connectionName,
            );
            $container->setDefinition(
                $aliveKeeperSvcId,
                $this->getAliveKeeperDefinition($container, $connectionName, $failoverConnections),
            );

            if ($checkActiveTransactions) {
                $decoratorAliveKeeperSvcId = sprintf(
                    '%s_%s',
                    TransactionDiscardingDBALAliveKeeper::class,
                    $connectionName,
                );
                $decoratedAliveKeeperSvcId = sprintf('%s.inner', $decoratorAliveKeeperSvcId);
                $transDiscardingDef = new ChildDefinition(TransactionDiscardingDBALAliveKeeper::class);
                $transDiscardingDef->setDecoratedService($aliveKeeperSvcId, $decoratedAliveKeeperSvcId, 1);
                $transDiscardingDef->setArgument('$decorated', new Reference($decoratedAliveKeeperSvcId));
                $container->setDefinition($decoratorAliveKeeperSvcId, $transDiscardingDef);
            }

            $passiveDecoratorAliveKeeperSvcId = sprintf(
                '%s_%s',
                PassiveIgnoringDBALAliveKeeper::class,
                $connectionName,
            );

            $ignorePassiveAliveKeeperSvcId = sprintf('%s.inner', $passiveDecoratorAliveKeeperSvcId);
            $passiveIgnoringDef = new ChildDefinition(PassiveIgnoringDBALAliveKeeper::class);
            $passiveIgnoringDef->setDecoratedService($aliveKeeperSvcId, $ignorePassiveAliveKeeperSvcId);
            $passiveIgnoringDef->setArgument('$decorated', new Reference($ignorePassiveAliveKeeperSvcId));
            $container->setDefinition($passiveDecoratorAliveKeeperSvcId, $passiveIgnoringDef);

            $aliveKeepers[$connectionName] = new Reference($aliveKeeperSvcId);

            if ($pingInterval === 0) {
                continue;
            }

            $optDecoratorAliveKeeperSvcId = sprintf('%s_%s', OptimizedDBALAliveKeeper::class, $connectionName);
            $optDecoratedAliveKeeperSvcId = sprintf('%s.inner', $optDecoratorAliveKeeperSvcId);
            $optimisedKeeperDef = new ChildDefinition(OptimizedDBALAliveKeeper::class);
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
    private function getAliveKeeperDefinition(
        ContainerBuilder $container,
        string $connectionName,
        array $failoverConnections,
    ): Reference | Definition {
        if (!isset($failoverConnections[$connectionName])) {
            return $container->findDefinition(PingingDBALAliveKeeper::class);
        }

        $aliveKeeper = new ChildDefinition(FailoverAwareDBALAliveKeeper::class);
        $aliveKeeper->setArgument('$connectionType', $failoverConnections[$connectionName]);

        return $aliveKeeper;
    }

    /**
     * @return array<Reference>
     */
    private function createRedisClusterConnectionReferences(ContainerBuilder $container): array
    {
        // @var array<string, string> $clusterConnections

        $clusterConnections = $container->getParameter(self::REDIS_CLUSTER_CONNECTIONS_PARAM_NAME);

        return array_map(
            static fn(string $connectionSvcId): Reference => new Reference($connectionSvcId),
            $clusterConnections,
        );
    }

    /**
     * @return array<ChildDefinition>
     */
    private function createRedisClusterAliveKeepers(ContainerBuilder $container): array
    {
        // @var array<string, string> $clusterConnections

        $clusterConnections = $container->getParameter(self::REDIS_CLUSTER_CONNECTIONS_PARAM_NAME);
        $pingInterval = (int) $container->hasParameter(Parameters::PING_INTERVAL)
            ? $container->getParameter(Parameters::PING_INTERVAL)
            : 0;
        // @var array<string> $excluded

        $excluded = $container->getParameter(Parameters::EXCLUDED_FROM_PROCESSING_REDIS_CLUSTER_CONNECTIONS);
        $aliveKeepers = [];

        foreach ($clusterConnections as $connectionName => $clusterSvcId) {
            if (in_array($connectionName, $excluded, true)) {
                continue;
            }

            $clusterDef = $container->findDefinition($clusterSvcId);
            $aliveKeeper = new ChildDefinition(PingingRedisClusterAliveKeeper::class);
            $aliveKeeper->setArgument('$constructorArguments', array_values($clusterDef->getArguments()));
            $aliveKeeperSvcId = sprintf(
                'pixel_federation_doctrine_resettable_em.alive_keeper.redis_cluster.%s',
                $connectionName,
            );
            $container->setDefinition($aliveKeeperSvcId, $aliveKeeper);

            $passiveDecoratorAliveKeeperSvcId = sprintf(
                '%s_%s',
                PassiveIgnoringRedisClusterAliveKeeper::class,
                $connectionName,
            );

            $ignorePassiveAliveKeeperSvcId = sprintf('%s.inner', $passiveDecoratorAliveKeeperSvcId);
            $passiveIgnoringDef = new ChildDefinition(PassiveIgnoringRedisClusterAliveKeeper::class);
            $passiveIgnoringDef->setDecoratedService($aliveKeeperSvcId, $ignorePassiveAliveKeeperSvcId);
            $passiveIgnoringDef->setArgument('$decorated', new Reference($ignorePassiveAliveKeeperSvcId));
            $container->setDefinition($passiveDecoratorAliveKeeperSvcId, $passiveIgnoringDef);

            $aliveKeepers[$connectionName] = new Reference($aliveKeeperSvcId);

            if ($pingInterval === 0) {
                continue;
            }

            $optDecoratorAliveKeeperSvcId = sprintf('%s_%s', OptimizedRedisClusterAliveKeeper::class, $connectionName);
            $optDecoratedAliveKeeperSvcId = sprintf('%s.inner', $optDecoratorAliveKeeperSvcId);
            $optimisedKeeperDef = new ChildDefinition(OptimizedRedisClusterAliveKeeper::class);
            $optimisedKeeperDef->setDecoratedService($aliveKeeperSvcId, $optDecoratedAliveKeeperSvcId, 2);
            $optimisedKeeperDef->setArgument('$decorated', new Reference($optDecoratedAliveKeeperSvcId));
            $optimisedKeeperDef->setArgument('$pingIntervalInSeconds', $pingInterval);
            $container->setDefinition($optDecoratorAliveKeeperSvcId, $optimisedKeeperDef);
        }

        return $aliveKeepers;
    }
}
