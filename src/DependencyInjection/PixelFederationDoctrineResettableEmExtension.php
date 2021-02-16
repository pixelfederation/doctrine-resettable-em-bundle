<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\DependencyInjection;

use Exception;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\AliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\OptimizedAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DependencyInjection\CompilerPass\AliveKeeperPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class PixelFederationDoctrineResettableEmExtension extends ConfigurableExtension
{
    /**
     * @param array $mergedConfig
     * @throws Exception
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
        $this->tryToOptimizeAliveKeeper($container, $mergedConfig);
        $this->registerReaderWriterConnections($container, $mergedConfig);
    }

    /**
     * @param array $config
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
     * @param array $config
     */
    private function registerReaderWriterConnections(ContainerBuilder $container, array $config): void
    {
        if (!isset($config['failover_connections']) || !is_array($config['failover_connections'])) {
            return;
        }

        $container->setParameter(
            AliveKeeperPass::FAILOVER_CONNECTIONS_PARAM_NAME,
            $config['failover_connections']
        );
    }
}
