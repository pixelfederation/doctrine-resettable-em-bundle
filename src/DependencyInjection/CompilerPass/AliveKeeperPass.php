<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace PixelFederation\DoctrineResettableEmBundle\DependencyInjection\CompilerPass;

use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\AggregatedAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\FailoverAware\FailoverAwareAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\SimpleAliveKeeper;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class AliveKeeperPass implements CompilerPassInterface
{
    public const FAILOVER_CONNECTIONS_PARAM_NAME = 'pixelfederation_doctrine_resettable_em_bundle.failover_connections';

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter(self::FAILOVER_CONNECTIONS_PARAM_NAME)) {
            return;
        }
        $connections = $container->getParameter('doctrine.connections');
        $failoverConnections = $container->getParameter(self::FAILOVER_CONNECTIONS_PARAM_NAME);
        $aliveKeepers = [];

        foreach ($connections as $connectionName => $connectionSvcId) {
            $aliveKeeperSvcId = sprintf('pixel_federation_doctrine_resettable_em.alive_keeper.%s', $connectionName);
            $aliveKeeper = $container->setDefinition(
                $aliveKeeperSvcId,
                $this->getAliveKeeperDefinition($connectionName, $failoverConnections)
            );
            $aliveKeeper->setArgument('$connection', new Reference($connectionSvcId));
            $aliveKeepers[] = new Reference($aliveKeeperSvcId);
        }

        $aggregatedAliveKeeper = $container->findDefinition(AggregatedAliveKeeper::class);
        $aggregatedAliveKeeper->setArgument('$aliveKeepers', $aliveKeepers);
    }

    private function getAliveKeeperDefinition(string $connectionName, array $failoverConnections): ChildDefinition
    {
        if (!isset($failoverConnections[$connectionName])) {
            return new ChildDefinition(SimpleAliveKeeper::class);
        }

        $aliveKeeper = new ChildDefinition(FailoverAwareAliveKeeper::class);
        $aliveKeeper->setArgument('$connectionName', $connectionName);
        $aliveKeeper->setArgument('$connectionType', $failoverConnections[$connectionName]);

        return $aliveKeeper;
    }
}
