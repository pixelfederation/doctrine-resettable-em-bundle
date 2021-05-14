<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace PixelFederation\DoctrineResettableEmBundle\DependencyInjection\CompilerPass;

use PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper\AggregatedAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\FailoverAware\FailoverAwareAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\DBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\RedisClusterAliveKeeper;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use UnexpectedValueException;

final class AliveKeeperPass implements CompilerPassInterface
{
    public const FAILOVER_CONNECTIONS_PARAM_NAME = 'pixelfederation_doctrine_resettable_em_bundle.failover_connections';
    const REDIS_CLUSTER_CONNECTIONS_PARAM_NAME =
        'pixelfederation_doctrine_resettable_em_bundle.redis_cluster_connections';

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter(self::FAILOVER_CONNECTIONS_PARAM_NAME)) {
            return;
        }

        $aliveKeepers = $this->createAliveKeepers($container);

        $aggregatedAliveKeeper = $container->findDefinition(AggregatedAliveKeeper::class);
        $aggregatedAliveKeeper->setArgument('$aliveKeepers', $aliveKeepers);
    }

    private function createAliveKeepers(ContainerBuilder $container): array
    {
        return array_merge(
            $this->createDoctrineAliveKeepers($container),
            $this->createRedisClusterAliveKeepers($container)
        );
    }

    private function createDoctrineAliveKeepers(ContainerBuilder $container): array
    {
        $connections = $container->getParameter('doctrine.connections');
        $failoverConnections = $container->getParameter(self::FAILOVER_CONNECTIONS_PARAM_NAME);
        $aliveKeepers = [];

        foreach ($connections as $connectionName => $connectionSvcId) {
            $aliveKeeperSvcId = sprintf('pixel_federation_doctrine_resettable_em.alive_keeper.%s', $connectionName);
            $aliveKeeper = $container->setDefinition(
                $aliveKeeperSvcId,
                $this->getAliveKeeperDefinition($connectionName, $failoverConnections)
            );
            $aliveKeeper->setArgument('$connection', new Reference($connectionSvcId));
            $aliveKeepers[] = new Reference($aliveKeeperSvcId);
        }

        return $aliveKeepers;
    }

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

    private function createRedisClusterAliveKeepers(ContainerBuilder $container): array
    {
        $clusterConnections = $container->getParameter(self::REDIS_CLUSTER_CONNECTIONS_PARAM_NAME);
        $aliveKeepers = [];

        foreach ($clusterConnections as $connectionName => $clusterSvcId) {
            $clusterDef = $container->findDefinition($clusterSvcId);

            if ($clusterDef === null) {
                throw new UnexpectedValueException(
                    sprintf('Unable to find redis cluster service - %s', $clusterSvcId)
                );
            }

            $aliveKeeper = new ChildDefinition(RedisClusterAliveKeeper::class);
            $aliveKeeper->setArgument('$connectionName', $connectionName);
            $aliveKeeper->setArgument('$redis', new Reference($clusterSvcId));
            $aliveKeeper->setArgument('$constructorArguments', array_values($clusterDef->getArguments()));
            $aliveKeepers[] = $aliveKeeper;
        }

        return $aliveKeepers;
    }
}
