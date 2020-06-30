<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 */
final class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder|void
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('pixel_federation_doctrine_resettable_em');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->children()
                ->scalarNode('ping_interval')
                    ->defaultFalse()
                ->end()
                ->variableNode('failover_connections')
                    ->info('Master slave connections reader/writer configuration.')
                    ->defaultValue([])
                    ->validate()
                    ->always(function ($connections) {
                        $validConnections = [];

                        foreach ((array) $connections as $connectionName => $connectionType) {
                            $connectionName = trim((string) $connectionName);
                            $connectionType = strtolower(trim((string) $connectionType));

                            $validConnections[$connectionName] = $connectionType;
                        }

                        return $validConnections;
                    })
                    ->end()
                ->end() // end failover_connections
            ->end();

        return $treeBuilder;
    }
}
