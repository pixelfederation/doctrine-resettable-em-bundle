<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection;

use Exception;
use RedisCluster;

final class OptimizedRedisClusterAliveKeeper implements RedisClusterAliveKeeper
{
    /**
     * @const int
     */
    private const DEFAULT_PING_INTERVAL = 0;

    private RedisClusterAliveKeeper $decorated;

    private int $pingIntervalInSeconds;

    private int $lastPingAt;

    public function __construct(
        RedisClusterAliveKeeper $decorated,
        int $pingIntervalInSeconds = self::DEFAULT_PING_INTERVAL
    ) {
        $this->decorated = $decorated;
        $this->pingIntervalInSeconds = $pingIntervalInSeconds;
        $this->lastPingAt = 0;
    }

    /**
     * @throws Exception
     */
    public function keepAlive(RedisCluster $redis, string $connectionName): void
    {
        if (!$this->isPingNeeded()) {
            return;
        }

        $this->decorated->keepAlive($redis, $connectionName);
    }

    /**
     * @throws Exception
     */
    private function isPingNeeded(): bool
    {
        $lastPingAt = $this->lastPingAt;
        $now = $this->lastPingAt = time();

        return $now - $lastPingAt >= $this->pingIntervalInSeconds;
    }
}
