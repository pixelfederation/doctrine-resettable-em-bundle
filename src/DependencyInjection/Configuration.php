<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('pixel_federation_doctrine_resettable_em');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->children()
                ->variableNode('exclude_from_resetting')
                    ->info('Entity manager names excluded from resetting.')
                    ->defaultValue([])
                    ->validate()
                    ->always(function ($emNames) {
                        $validEmNames = [];

                        foreach ((array) $emNames as $emName) {
                            $emName = trim((string) $emName);
                            $validEmNames[] = $emName;
                        }

                        return $validEmNames;
                    })
                    ->end()
                ->end()
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
                ->variableNode('redis_cluster_connections')
                    ->info('Redis cluster connections for alive keeping.')
                    ->defaultValue([])
                    ->validate()
                    ->always(function (array $connections) {
                        $validConnections = [];

                        foreach ($connections as $connectionName => $connectionValue) {
                            $connectionName = trim((string) $connectionName);
                            $connectionValue = trim((string) $connectionValue);

                            if ($connectionName !== '' && $connectionValue !== '') {
                                $validConnections[$connectionName] = $connectionValue;
                            }
                        }

                        return $validConnections;
                    })
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
