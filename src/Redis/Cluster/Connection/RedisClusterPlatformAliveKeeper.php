<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection;

use PixelFederation\DoctrineResettableEmBundle\Connection\PlatformAliveKeeper as GenericPlatformAliveKeeper;
use RedisCluster;
use RuntimeException;

final class RedisClusterPlatformAliveKeeper implements GenericPlatformAliveKeeper
{
    /**
     * @var array<string, RedisCluster>
     */
    private array $connections;

    /**
     * @var array<string, RedisClusterAliveKeeper>
     */
    private array $aliveKeepers;

    /**
     * @param array<string, RedisCluster> $connections
     * @param array<string, RedisClusterAliveKeeper> $aliveKeepers
     */
    public function __construct(array $connections, array $aliveKeepers)
    {
        $this->connections = $connections;
        $this->aliveKeepers = $aliveKeepers;
    }

    public function keepAlive(): void
    {
        foreach ($this->aliveKeepers as $connectionName => $aliveKeeper) {
            if (!isset($this->connections[$connectionName])) {
                throw new RuntimeException(
                    sprintf('Connection "%s" is missing.', $connectionName)
                );
            }

            $connection = $this->connections[$connectionName];
            $aliveKeeper->keepAlive($connection, $connectionName);
        }
    }
}
