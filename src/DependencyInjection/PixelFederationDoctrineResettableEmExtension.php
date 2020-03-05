<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\DependencyInjection;

use Exception;
use PixelFederation\DoctrineResettableEmBundle\DBAL\ConnectionsHandler;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 */
final class PixelFederationDoctrineResettableEmExtension extends ConfigurableExtension
{
    /**
     * @param array            $mergedConfig
     * @param ContainerBuilder $container
     *
     * @throws Exception
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        if (isset($mergedConfig['ping_interval']) && gettype($mergedConfig['ping_interval']) === 'integer') {
            $connectionsHandler = $container->getDefinition(ConnectionsHandler::class);
            $connectionsHandler->setArgument('$pingIntervalInSeconds', $mergedConfig['ping_interval']);
        }
    }
}
