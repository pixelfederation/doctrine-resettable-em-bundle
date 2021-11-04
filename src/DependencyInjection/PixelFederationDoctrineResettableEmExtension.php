<?php

declare(strict_types=1);

/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\DependencyInjection;

use Exception;
use PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper\AliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper\OptimizedAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DependencyInjection\CompilerPass\AliveKeeperPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class PixelFederationDoctrineResettableEmExtension extends ConfigurableExtension
{
    /**
     * @param array{
     *     exclude_from_resetting: array<string>,
     *     redis_cluster_connections?: array<string, string>
     * } $mergedConfig
     * @throws Exception
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
        $this->registerNotResettableEntityManagers($container, $mergedConfig);
        $this->tryToOptimizeAliveKeeper($container, $mergedConfig);
        $this->registerReaderWriterConnections($container, $mergedConfig);
        $this->registerRedisClusterConnections($container, $mergedConfig);
    }

    /**
     * @param array{exclude_from_resetting: array<string>} $config
     */
    private function registerNotResettableEntityManagers(ContainerBuilder $container, array $config): void
    {
        $container->setParameter(Parameters::EXCLUDED_FROM_RESETTING, $config['exclude_from_resetting']);
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
        $connectionsHandler = $container->getDefinition(OptimizedAliveKeeper::class);
        $connectionsHandler->setArgument('$pingIntervalInSeconds', $pingInterval);
        $container->setAlias(AliveKeeper::class, new Alias(OptimizedAliveKeeper::class));
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
            $config['redis_cluster_connections']
        );
    }
}
