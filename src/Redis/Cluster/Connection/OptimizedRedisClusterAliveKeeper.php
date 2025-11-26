<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection;

use Override;
use RedisCluster;

final class OptimizedRedisClusterAliveKeeper implements RedisClusterAliveKeeper
{
    private const int DEFAULT_PING_INTERVAL = 0;

    private int $lastPingAt;

    public function __construct(
        private readonly RedisClusterAliveKeeper $decorated,
        private readonly int $pingIntervalInSeconds = self::DEFAULT_PING_INTERVAL,
    ) {
        $this->lastPingAt = 0;
    }

    #[Override]
    public function keepAlive(RedisCluster $redis, string $connectionName): void
    {
        if (!$this->isPingNeeded()) {
            return;
        }

        $this->decorated->keepAlive($redis, $connectionName);
    }

    private function isPingNeeded(): bool
    {
        $lastPingAt = $this->lastPingAt;
        $now = time();
        $this->lastPingAt = $now;

        return $now - $lastPingAt >= $this->pingIntervalInSeconds;
    }
}
