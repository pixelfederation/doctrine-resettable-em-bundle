<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DependencyInjection;

use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\FailoverAware\ConnectionType;
use PixelFederation\DoctrineResettableEmBundle\RequestCycle\Initializers;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * @psalm-type ConfigType = array{
 *     exclude_from_processing: array{
 *         entity_managers: array<string>,
 *         connections: array{
 *             dbal: array<string>,
 *             redis_cluster: array<string>
 *         }
 *     },
 *     redis_cluster_connections?: array<string, string>,
 *     ping_interval?: string|int|false,
 *     check_active_transactions?: bool,
 *     disable_request_initializers?: bool,
 *     failover_connections?: array<string, ConnectionType>,
 *  }
 */
final class PixelFederationDoctrineResettableEmExtension extends ConfigurableExtension
{
    public const string EXCLUDED_FROM_PROCESSING_ENTITY_MANAGERS =
        'pixelfederation_doctrine_resettable_em_bundle.excluded_from_processing.entity_managers';

    public const string EXCLUDED_FROM_PROCESSING_DBAL_CONNECTIONS =
        'pixelfederation_doctrine_resettable_em_bundle.excluded_from_processing.connections.dbal';

    public const string EXCLUDED_FROM_PROCESSING_REDIS_CLUSTER_CONNECTIONS =
        'pixelfederation_doctrine_resettable_em_bundle.excluded_from_processing.connections.redis_cluster';

    public const string PING_INTERVAL = 'pixelfederation_doctrine_resettable_em_bundle.ping_interval';

    public const string CHECK_ACTIVE_TRANSACTIONS =
        'pixelfederation_doctrine_resettable_em_bundle.check_active_transactions';

    public const string FAILOVER_CONNECTIONS_PARAM_NAME =
        'pixelfederation_doctrine_resettable_em_bundle.failover_connections';

    public const string REDIS_CLUSTER_CONNECTIONS_PARAM_NAME =
        'pixelfederation_doctrine_resettable_em_bundle.redis_cluster_connections';

    /**
     * @param ConfigType $mergedConfig
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');
        $this->registerNotResettableEntityManagers(
            $container,
            $mergedConfig['exclude_from_processing']['entity_managers'],
        );
        $this->registerNotPingableDbalConnections(
            $container,
            $mergedConfig['exclude_from_processing']['connections']['dbal'],
        );
        $this->registerNotPingableRedisClusterConnections(
            $container,
            $mergedConfig['exclude_from_processing']['connections']['redis_cluster'],
        );
        $this->tryToOptimizeAliveKeeper($container, $mergedConfig);
        $this->tryToActivateTransactionChecks($container, $mergedConfig);
        $this->registerReaderWriterConnections($container, $mergedConfig);
        $this->registerRedisClusterConnections($container, $mergedConfig);
        $this->registerInitializers($container, $mergedConfig);
    }

    /**
     * @param array<string> $config
     */
    private function registerNotResettableEntityManagers(ContainerBuilder $container, array $config): void
    {
        $container->setParameter(self::EXCLUDED_FROM_PROCESSING_ENTITY_MANAGERS, array_unique($config));
    }

    /**
     * @param array<string> $config
     */
    private function registerNotPingableDbalConnections(ContainerBuilder $container, array $config): void
    {
        $container->setParameter(self::EXCLUDED_FROM_PROCESSING_DBAL_CONNECTIONS, array_unique($config));
    }

    /**
     * @param array<string> $config
     */
    private function registerNotPingableRedisClusterConnections(ContainerBuilder $container, array $config): void
    {
        $container->setParameter(self::EXCLUDED_FROM_PROCESSING_REDIS_CLUSTER_CONNECTIONS, array_unique($config));
    }

    /**
     * @param ConfigType $config
     */
    private function tryToOptimizeAliveKeeper(ContainerBuilder $container, array $config): void
    {
        if (!isset($config['ping_interval']) || $config['ping_interval'] === false) {
            return;
        }

        $pingInterval = (int) $config['ping_interval'];
        $container->setParameter(self::PING_INTERVAL, $pingInterval);
    }

    /**
     * @param ConfigType $config
     */
    private function tryToActivateTransactionChecks(ContainerBuilder $container, array $config): void
    {
        if (!isset($config['check_active_transactions']) || $config['check_active_transactions'] === false) {
            return;
        }

        $checkActiveTransactions = $config['check_active_transactions'];
        $container->setParameter(self::CHECK_ACTIVE_TRANSACTIONS, $checkActiveTransactions);
    }

    /**
     * @param ConfigType $config
     */
    private function registerReaderWriterConnections(ContainerBuilder $container, array $config): void
    {
        $failoverConnections = $config['failover_connections'] ?? [];
        $container->setParameter(self::FAILOVER_CONNECTIONS_PARAM_NAME, $failoverConnections);
    }

    /**
     * @param ConfigType $config
     */
    private function registerRedisClusterConnections(ContainerBuilder $container, array $config): void
    {
        if (!isset($config['redis_cluster_connections'])) {
            return;
        }

        $container->setParameter(
            self::REDIS_CLUSTER_CONNECTIONS_PARAM_NAME,
            $config['redis_cluster_connections'],
        );
    }

    /**
     * @param ConfigType $config
     */
    private function registerInitializers(ContainerBuilder $container, array $config): void
    {
        $disable = $config['disable_request_initializers'] ?? false;
        if ($disable) {
            return;
        }

        $initializers = new Definition(Initializers::class, [
            '$initializers' => new TaggedIteratorArgument(
                'pixelfederation_doctrine_resettable_em_bundle.app_initializer',
            ),
        ]);
        $initializers->addTag('kernel.event_listener', [
            'event' => 'kernel.request',
            'method' => 'initialize',
            'priority' => 1000000,
        ]);
        $container->setDefinition(Initializers::class, $initializers);
    }
}
