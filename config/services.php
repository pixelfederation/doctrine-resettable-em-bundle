<?php

declare(strict_types=1);

use PixelFederation\DoctrineResettableEmBundle\Connection\ConnectionsHandler;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\DBALPlatformAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\FailoverAware\FailoverAwareDBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\OptimizedDBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\PassiveIgnoringDBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\PingingDBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\TransactionDiscardingDBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\OptimizedRedisClusterAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\PassiveIgnoringRedisClusterAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\PingingRedisClusterAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\RedisClusterPlatformAliveKeeper;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults();

    $services->set(ConnectionsHandler::class)
        ->arg('$aliveKeepers', null);

    $services->set(DBALPlatformAliveKeeper::class)
        ->arg('$connections', null)
        ->arg('$aliveKeepers', null);

    $services->set(OptimizedDBALAliveKeeper::class)
        ->abstract(true)
        ->arg('$decorated', null);

    $services->set(PingingDBALAliveKeeper::class);

    $services->set(TransactionDiscardingDBALAliveKeeper::class)
        ->abstract(true)
        ->arg('$decorated', null)
        ->arg('$logger', service('logger'))
        ->tag('monolog.logger', ['channel' => 'doctrine-resettable-em-bundle']);

    $services->set(PassiveIgnoringDBALAliveKeeper::class)
        ->abstract(true)
        ->arg('$decorated', null);

    $services->set(RedisClusterPlatformAliveKeeper::class)
        ->arg('$connections', null)
        ->arg('$aliveKeepers', null);

    $services->set(PingingRedisClusterAliveKeeper::class)
        ->abstract(true)
        ->arg('$constructorArguments', null)
        ->arg('$logger', service('logger'))
        ->tag('monolog.logger', ['channel' => 'doctrine-resettable-em-bundle']);

    $services->set(PassiveIgnoringRedisClusterAliveKeeper::class)
        ->abstract(true)
        ->arg('$decorated', null);

    $services->set(FailoverAwareDBALAliveKeeper::class)
        ->abstract(true)
        ->arg('$logger', service('logger'))
        ->arg('$connectionType', null)
        ->tag('monolog.logger', ['channel' => 'doctrine-resettable-em-bundle']);

    $services->set(OptimizedRedisClusterAliveKeeper::class)
        ->abstract(true)
        ->arg('$decorated', null);
};
