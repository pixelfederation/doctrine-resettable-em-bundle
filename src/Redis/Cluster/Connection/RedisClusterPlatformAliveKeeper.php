<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection;

use PixelFederation\DoctrineResettableEmBundle\Connection\PlatformAliveKeeper as GenericPlatformAliveKeeper;
use RedisCluster;
use RuntimeException;

final class RedisClusterPlatformAliveKeeper implements GenericPlatformAliveKeeper
{
    /**
     * @param array<string, RedisCluster> $connections
     * @param array<string, RedisClusterAliveKeeper> $aliveKeepers
     */
    public function __construct(
        private array $connections,
        private readonly array $aliveKeepers,
    ) {
    }

    public function keepAlive(): void
    {
        foreach ($this->aliveKeepers as $connectionName => $aliveKeeper) {
            if (!isset($this->connections[$connectionName])) {
                throw new RuntimeException(
                    sprintf('Connection "%s" is missing.', $connectionName),
                );
            }

            $connection = $this->connections[$connectionName];
            $aliveKeeper->keepAlive($connection, $connectionName);
        }
    }
}
