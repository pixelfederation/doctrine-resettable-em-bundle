<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\DependencyInjection;

use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\FailoverAware\ConnectionType;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;

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

                            if ($connectionName === '' || $connectionType === '' ||
                                !in_array($connectionType, [ConnectionType::WRITER, ConnectionType::READER], true)) {
                                throw new InvalidTypeException(
                                    sprintf(
                                        'Invalid connection type %s for connection %s.',
                                        $connectionType,
                                        $connectionName
                                    )
                                );
                            }

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
