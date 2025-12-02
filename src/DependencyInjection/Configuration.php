<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DependencyInjection;

use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\FailoverAware\ConnectionType;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder<'array'>
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pixel_federation_doctrine_resettable_em');
        $rootNode = $treeBuilder->getRootNode();

        $this->addExcludeFromProcessing($rootNode);
        $rootNode->children()
            ->scalarNode('ping_interval')
                ->defaultFalse();
        $rootNode->children()
            ->booleanNode('check_active_transactions')
                ->defaultFalse();
        $this->addFailoverConnections($rootNode);
        $this->addRedisClusterConnections($rootNode);
        $rootNode->children()
            ->booleanNode('disable_request_initializers')
                ->defaultFalse();

        return $treeBuilder;
    }

    /**
     * @param ArrayNodeDefinition<TreeBuilder<'array'>> $rootNode
     */
    private function addExcludeFromProcessing(ArrayNodeDefinition $rootNode): void
    {
        $excludeFromProcessing = $rootNode->children()->arrayNode('exclude_from_processing');
        $excludeFromProcessing->addDefaultsIfNotSet();
        $this->addExcludeFromProcessingEntityManagers($excludeFromProcessing);
        $this->addExcludeFromProcessingConnections($excludeFromProcessing);
    }

    /**
     * @param ArrayNodeDefinition<NodeBuilder<ArrayNodeDefinition<TreeBuilder<'array'>>>> $parentNode
     */
    private function addExcludeFromProcessingConnections(ArrayNodeDefinition $parentNode): void
    {
        $connections = $parentNode->children()
            ->arrayNode('connections')
            ->addDefaultsIfNotSet();

        $dbal = $connections->children()
            ->arrayNode('dbal')
            ->info('DBAL connection names excluded from processing.')
            ->defaultValue([]);
        $dbal->stringPrototype();
        $dbal->validate()
            ->always(static function ($connectionNames) {
                /** @var array<string> $connectionNames */
                $validNames = [];
                foreach ((array) $connectionNames as $connectionName) {
                    $connectionName = trim($connectionName);
                    $validNames[] = $connectionName;
                }

                return $validNames;
            });

        $redisCluster = $connections->children()
            ->arrayNode('redis_cluster')
            ->info('RedisCluster connection names excluded from processing.')
            ->defaultValue([]);
        $redisCluster->stringPrototype();
        $redisCluster->validate()
            ->always(static function (array $connectionNames) {
                /** @var array<string> $connectionNames */
                $validNames = [];
                foreach ($connectionNames as $connectionName) {
                    $connectionName = trim($connectionName);
                    $validNames[] = $connectionName;
                }

                return $validNames;
            });
    }

    /**
     * @param ArrayNodeDefinition<NodeBuilder<ArrayNodeDefinition<TreeBuilder<'array'>>>> $parentNode
     */
    private function addExcludeFromProcessingEntityManagers(ArrayNodeDefinition $parentNode): void
    {
        $entityManagers = $parentNode->children()
            ->arrayNode('entity_managers')
            ->info('Entity manager names excluded from processing.')
            ->defaultValue([]);
        $entityManagers->stringPrototype();
        $entityManagers->validate()
            ->always(static function (array $emNames) {
                /** @var array<string> $emNames */
                $validEmNames = [];
                foreach ($emNames as $emName) {
                    $emName = trim($emName);
                    $validEmNames[] = $emName;
                }

                return $validEmNames;
            });
    }

    /**
     * @param ArrayNodeDefinition<TreeBuilder<'array'>> $rootNode
     */
    private function addFailoverConnections(ArrayNodeDefinition $rootNode): void
    {
        $failoverConnections = $rootNode->children()
            ->arrayNode('failover_connections')
                ->info('Master slave connections reader/writer configuration.')
                ->defaultValue([]);
        $failoverConnections->stringPrototype();
        $failoverConnections->validate()
            ->always(static function ($connections): array {
                /** @var array<string, string> $connections */
                $validConnections = [];

                foreach ((array) $connections as $connectionName => $connectionType) {
                    $connectionName = trim($connectionName);
                    $connectionType = ConnectionType::from(strtolower(trim($connectionType)));

                    $validConnections[$connectionName] = $connectionType;
                }

                return $validConnections;
            });
    }

    /**
     * @param ArrayNodeDefinition<TreeBuilder<'array'>> $rootNode
     */
    // phpcs:ignore SlevomatCodingStandard.Complexity.Cognitive.ComplexityTooHigh
    private function addRedisClusterConnections(ArrayNodeDefinition $rootNode): void
    {
        $redisClusterConnections = $rootNode->children()
            ->arrayNode('redis_cluster_connections')
                ->info('Redis cluster connections for alive keeping.')
                ->defaultValue([]);
        $redisClusterConnections->stringPrototype();
        $redisClusterConnections->validate()
            ->always(static function (array $connections): array {
                /** @var array<string, string> $connections */
                $validConnections = [];
                foreach ($connections as $connectionName => $connectionValue) {
                    $connectionName = trim($connectionName);
                    $connectionValue = trim($connectionValue);
                    if ($connectionName === '' || $connectionValue === '') {
                        continue;
                    }

                    $validConnections[$connectionName] = $connectionValue;
                }

                return $validConnections;
            });
    }
}
