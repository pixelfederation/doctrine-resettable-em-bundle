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
use PixelFederation\DoctrineResettableEmBundle\DependencyInjection\PixelFederationDoctrineResettableEmExtension;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\OptimizedRedisClusterAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\PassiveIgnoringRedisClusterAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\PingingRedisClusterAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\RedisClusterPlatformAliveKeeper;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class AliveKeeperPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter(PixelFederationDoctrineResettableEmExtension::FAILOVER_CONNECTIONS_PARAM_NAME)) {
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
        $pingInterval = $this->getPingInterval($container);
        $aliveKeepers = [];
        $dbalAliveKeeper = $this->createDBALPlatformAliveKeeper($container, $pingInterval);

        if ($dbalAliveKeeper !== null) {
            $aliveKeepers[] = $dbalAliveKeeper;
        }

        $redisClusterAliveKeeper = $this->createRedisClusterPlatformAliveKeeper($container, $pingInterval);

        if ($redisClusterAliveKeeper !== null) {
            $aliveKeepers[] = $redisClusterAliveKeeper;
        }

        return $aliveKeepers;
    }

    private function createDBALPlatformAliveKeeper(ContainerBuilder $container, int $pingInterval): ?Reference
    {
        /** @var array<string, string> $connections */
        $connections = $container->getParameter('doctrine.connections');
        $aliveKeepers = $this->createDBALAliveKeepers($container, $pingInterval, $connections);
        if (count($aliveKeepers) === 0) {
            return null;
        }
        $connectionRefs = [];

        foreach ($connections as $connectionName => $connectionSvcId) {
            $connectionRefs[$connectionName] = new Reference($connectionSvcId);
        }

        $aliveKeeperDef = $container->findDefinition(DBALPlatformAliveKeeper::class);
        $aliveKeeperDef->setArgument('$connections', $connectionRefs);
        $aliveKeeperDef->setArgument('$aliveKeepers', $aliveKeepers);

        return new Reference(DBALPlatformAliveKeeper::class);
    }

    private function createRedisClusterPlatformAliveKeeper(ContainerBuilder $container, int $pingInterval): ?Reference
    {
        $connections = $this->createRedisClusterConnectionReferences($container);
        if (count($connections) === 0) {
            return null;
        }

        $aliveKeepers = $this->createRedisClusterAliveKeepers($container, $pingInterval);
        if (count($aliveKeepers) === 0) {
            return null;
        }

        $aliveKeeperDef = $container->findDefinition(RedisClusterPlatformAliveKeeper::class);
        $aliveKeeperDef->setArgument('$connections', $connections);
        $aliveKeeperDef->setArgument('$aliveKeepers', $aliveKeepers);

        return new Reference(RedisClusterPlatformAliveKeeper::class);
    }

    /**
     * @param array<string, string> $connections
     * @return array<Reference>
     */
    private function createDBALAliveKeepers(
        ContainerBuilder $container,
        int $pingInterval,
        array $connections,
    ): array {
        /** @var array<string, string> $failoverConnections */
        $failoverConnections = $container->getParameter(
            PixelFederationDoctrineResettableEmExtension::FAILOVER_CONNECTIONS_PARAM_NAME,
        );
        $checkActiveTransactions = $this->getCheckActiveTransactions($container);
        /** @var array<string> $excluded */
        $excluded = $container->getParameter(
            PixelFederationDoctrineResettableEmExtension::EXCLUDED_FROM_PROCESSING_DBAL_CONNECTIONS,
        );
        $aliveKeepers = [];

        foreach (array_keys($connections) as $connectionName) {
            if (in_array($connectionName, $excluded, true)) {
                continue;
            }
            $aliveKeepers = $this->addDBALAliveKeeper(
                $container,
                $connectionName,
                $failoverConnections,
                $pingInterval,
                $checkActiveTransactions,
                $aliveKeepers,
            );
        }

        return $aliveKeepers;
    }

    /**
     * @param array<string, string> $failoverConnections
     * @param array<string, Reference> $aliveKeepers
     * @return array<string, Reference>
     */
    private function addDBALAliveKeeper(
        ContainerBuilder $container,
        string $connectionName,
        array $failoverConnections,
        int $pingInterval,
        bool $checkActiveTransactions,
        array $aliveKeepers,
    ): array {
        $aliveKeeperSvcId = sprintf(
            'pixel_federation_doctrine_resettable_em.alive_keeper.dbal.%s',
            $connectionName,
        );
        $container->setDefinition(
            $aliveKeeperSvcId,
            $this->getAliveKeeperDefinition($container, $connectionName, $failoverConnections),
        );

        if ($checkActiveTransactions) {
            $this->createTransactionDiscardingDBALAliveKeeper($container, $connectionName, $aliveKeeperSvcId);
        }
        $this->createPassiveIgnoringDBALAliveKeeper($container, $connectionName, $aliveKeeperSvcId);
        $aliveKeepers[$connectionName] = new Reference($aliveKeeperSvcId);

        if ($pingInterval === 0) {
            return $aliveKeepers;
        }

        $this->createOptimizedDBALAliveKeeper($container, $connectionName, $aliveKeeperSvcId, $pingInterval);

        return $aliveKeepers;
    }

    private function createPassiveIgnoringDBALAliveKeeper(
        ContainerBuilder $container,
        string $connectionName,
        string $aliveKeeperSvcId,
    ): void {
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
    }

    private function createTransactionDiscardingDBALAliveKeeper(
        ContainerBuilder $container,
        string $connectionName,
        string $aliveKeeperSvcId,
    ): void {
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

    private function createOptimizedDBALAliveKeeper(
        ContainerBuilder $container,
        string $connectionName,
        string $aliveKeeperSvcId,
        int $pingInterval,
    ): void {
        $optDecoratorAliveKeeperSvcId = sprintf('%s_%s', OptimizedDBALAliveKeeper::class, $connectionName);
        $optDecoratedAliveKeeperSvcId = sprintf('%s.inner', $optDecoratorAliveKeeperSvcId);
        $optimisedKeeperDef = new ChildDefinition(OptimizedDBALAliveKeeper::class);
        $optimisedKeeperDef->setDecoratedService($aliveKeeperSvcId, $optDecoratedAliveKeeperSvcId, 2);
        $optimisedKeeperDef->setArgument('$decorated', new Reference($optDecoratedAliveKeeperSvcId));
        $optimisedKeeperDef->setArgument('$pingIntervalInSeconds', $pingInterval);
        $container->setDefinition($optDecoratorAliveKeeperSvcId, $optimisedKeeperDef);
    }

    /**
     * @param array<string, string> $failoverConnections
     */
    private function getAliveKeeperDefinition(
        ContainerBuilder $container,
        string $connectionName,
        array $failoverConnections,
    ): Definition {
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
        if (
            !$container->hasParameter(
                PixelFederationDoctrineResettableEmExtension::REDIS_CLUSTER_CONNECTIONS_PARAM_NAME,
            )
        ) {
            return [];
        }

        /** @var array<string, string> $clusterConnections */
        $clusterConnections = $container->getParameter(
            PixelFederationDoctrineResettableEmExtension::REDIS_CLUSTER_CONNECTIONS_PARAM_NAME,
        );

        return array_map(
            static fn (string $connectionSvcId): Reference => new Reference($connectionSvcId),
            $clusterConnections,
        );
    }

    /**
     * @return array<string, Reference>
     */
    private function createRedisClusterAliveKeepers(ContainerBuilder $container, int $pingInterval): array
    {
        /** @var array<string, string> $clusterConnections */
        $clusterConnections = $container->getParameter(
            PixelFederationDoctrineResettableEmExtension::REDIS_CLUSTER_CONNECTIONS_PARAM_NAME,
        );
        /** @var array<string> $excluded */
        $excluded = $container->getParameter(
            PixelFederationDoctrineResettableEmExtension::EXCLUDED_FROM_PROCESSING_REDIS_CLUSTER_CONNECTIONS,
        );
        $aliveKeepers = [];

        foreach ($clusterConnections as $connectionName => $clusterSvcId) {
            if (in_array($connectionName, $excluded, true)) {
                continue;
            }

            $aliveKeepers = $this->addRedisClusterAliveKeeper(
                $container,
                $clusterSvcId,
                $connectionName,
                $pingInterval,
                $aliveKeepers,
            );
        }

        return $aliveKeepers;
    }

    /**
     * @param array<string, Reference> $aliveKeepers
     * @return array<string, Reference>
     */
    private function addRedisClusterAliveKeeper(
        ContainerBuilder $container,
        string $clusterSvcId,
        string $connectionName,
        int $pingInterval,
        array $aliveKeepers,
    ): array {
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
            return $aliveKeepers;
        }

        $optDecoratorAliveKeeperSvcId = sprintf('%s_%s', OptimizedRedisClusterAliveKeeper::class, $connectionName);
        $optDecoratedAliveKeeperSvcId = sprintf('%s.inner', $optDecoratorAliveKeeperSvcId);
        $optimisedKeeperDef = new ChildDefinition(OptimizedRedisClusterAliveKeeper::class);
        $optimisedKeeperDef->setDecoratedService($aliveKeeperSvcId, $optDecoratedAliveKeeperSvcId, 2);
        $optimisedKeeperDef->setArgument('$decorated', new Reference($optDecoratedAliveKeeperSvcId));
        $optimisedKeeperDef->setArgument('$pingIntervalInSeconds', $pingInterval);
        $container->setDefinition($optDecoratorAliveKeeperSvcId, $optimisedKeeperDef);

        return $aliveKeepers;
    }

    private function getPingInterval(ContainerBuilder $container): int
    {
        if (!$container->hasParameter(PixelFederationDoctrineResettableEmExtension::PING_INTERVAL)) {
            return 0;
        }

        $pingInterval = $container->getParameter(PixelFederationDoctrineResettableEmExtension::PING_INTERVAL);
        assert(is_int($pingInterval));

        return $pingInterval;
    }

    private function getCheckActiveTransactions(ContainerBuilder $container): bool
    {
        if (!$container->hasParameter(PixelFederationDoctrineResettableEmExtension::CHECK_ACTIVE_TRANSACTIONS)) {
            return false;
        }

        $checkActiveTransactions = $container->getParameter(
            PixelFederationDoctrineResettableEmExtension::CHECK_ACTIVE_TRANSACTIONS,
        );
        assert(is_bool($checkActiveTransactions));

        return $checkActiveTransactions;
    }
}
