<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pixel_federation_doctrine_resettable_em');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->children()
            ->arrayNode('exclude_from_processing')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('entity_managers')
                        ->info('Entity manager names excluded from processing.')
                        ->scalarPrototype()->end()
                        ->defaultValue([])
                        ->validate()
                        ->always(static function ($emNames) {
                            $validEmNames = [];

                            foreach ((array) $emNames as $emName) {
                                $emName = trim((string) $emName);
                                $validEmNames[] = $emName;
                            }

                            return $validEmNames;
                        })
                        ->end()
                    ->end()
                    ->arrayNode('connections')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->arrayNode('dbal')
                                ->info('DBAL connection names excluded from processing.')
                                ->scalarPrototype()->end()
                                ->defaultValue([])
                                ->validate()
                                ->always(static function ($connectionNames) {
                                    $validNames = [];

                                    foreach ((array) $connectionNames as $connectionName) {
                                        $connectionName = trim((string) $connectionName);
                                        $validNames[] = $connectionName;
                                    }

                                    return $validNames;
                                })
                                ->end()
                            ->end()
                            ->arrayNode('redis_cluster')
                                ->info('RedisCluster connection names excluded from processing.')
                                ->scalarPrototype()->end()
                                ->defaultValue([])
                                ->validate()
                                ->always(static function ($connectionNames) {
                                    $validNames = [];

                                    foreach ((array) $connectionNames as $connectionName) {
                                        $connectionName = trim((string) $connectionName);
                                        $validNames[] = $connectionName;
                                    }

                                    return $validNames;
                                })
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->scalarNode('ping_interval')
                ->defaultFalse()
            ->end()
            ->variableNode('failover_connections')
                ->info('Master slave connections reader/writer configuration.')
                ->defaultValue([])
                ->validate()
                ->always(static function ($connections): array {
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
                ->always(static function (array $connections): array {
                    $validConnections = [];

                    foreach ($connections as $connectionName => $connectionValue) {
                        $connectionName = trim((string) $connectionName);
                        $connectionValue = trim((string) $connectionValue);

                        if ($connectionName === '' || $connectionValue === '') {
                            continue;
                        }

                        $validConnections[$connectionName] = $connectionValue;
                    }

                    return $validConnections;
                })
                ->end()
            ->end();

        return $treeBuilder;
    }
}
