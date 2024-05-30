<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DependencyInjection;

use Exception;
use PixelFederation\DoctrineResettableEmBundle\DependencyInjection\CompilerPass\AliveKeeperPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class PixelFederationDoctrineResettableEmExtension extends ConfigurableExtension
{
    /**
     * @param array{
     *     exclude_from_processing: array{
     *         entity_managers: array<string>,
     *         connections: array{
     *             dbal: array<string>,
     *             redis_cluster: array<string>
     *         }
     *     },
     *     redis_cluster_connections?: array<string, string>,
     *     ping_interval?: int|false,
     *     check_active_transactions?: bool
     * } $mergedConfig
     * @throws Exception
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
    }

    /**
     * @param array<string> $config
     */
    private function registerNotResettableEntityManagers(ContainerBuilder $container, array $config): void
    {
        $container->setParameter(Parameters::EXCLUDED_FROM_PROCESSING_ENTITY_MANAGERS, array_unique($config));
    }

    /**
     * @param array<string> $config
     */
    private function registerNotPingableDbalConnections(ContainerBuilder $container, array $config): void
    {
        $container->setParameter(Parameters::EXCLUDED_FROM_PROCESSING_DBAL_CONNECTIONS, array_unique($config));
    }

    /**
     * @param array<string> $config
     */
    private function registerNotPingableRedisClusterConnections(ContainerBuilder $container, array $config): void
    {
        $container->setParameter(Parameters::EXCLUDED_FROM_PROCESSING_REDIS_CLUSTER_CONNECTIONS, array_unique($config));
    }

    /**
     * @param array{ping_interval?: int|false} $config
     */
    private function tryToOptimizeAliveKeeper(ContainerBuilder $container, array $config): void
    {
        if (!isset($config['ping_interval']) || $config['ping_interval'] === false) {
            return;
        }

        $pingInterval = intval($config['ping_interval']);
        $container->setParameter(Parameters::PING_INTERVAL, $pingInterval);
    }

    /**
     * @param array{check_active_transactions?: bool} $config
     */
    private function tryToActivateTransactionChecks(ContainerBuilder $container, array $config): void
    {
        if (!isset($config['check_active_transactions']) || $config['check_active_transactions'] === false) {
            return;
        }

        $checkActiveTransactions = $config['check_active_transactions'];
        $container->setParameter(Parameters::CHECK_ACTIVE_TRANSACTIONS, $checkActiveTransactions);
    }

    /**
     * @param array{failover_connections?: array<string, string>} $config
     */
    private function registerReaderWriterConnections(ContainerBuilder $container, array $config): void
    {
        if (!isset($config['failover_connections'])) {
            return;
        }

        $container->setParameter(AliveKeeperPass::FAILOVER_CONNECTIONS_PARAM_NAME, $config['failover_connections']);
    }

    /**
     * @param array{redis_cluster_connections?: array<string, string>} $config
     */
    private function registerRedisClusterConnections(ContainerBuilder $container, array $config): void
    {
        if (!isset($config['redis_cluster_connections'])) {
            return;
        }

        $container->setParameter(
            AliveKeeperPass::REDIS_CLUSTER_CONNECTIONS_PARAM_NAME,
            $config['redis_cluster_connections'],
        );
    }
}
