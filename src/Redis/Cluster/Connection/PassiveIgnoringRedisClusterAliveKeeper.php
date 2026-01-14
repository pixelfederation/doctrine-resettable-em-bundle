<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection;

use Override;
use RedisCluster;
use Symfony\Component\VarExporter\LazyObjectInterface;

final class PassiveIgnoringRedisClusterAliveKeeper implements RedisClusterAliveKeeper
{
    public function __construct(
        private readonly RedisClusterAliveKeeper $decorated,
    ) {
    }

    #[Override]
    public function keepAlive(RedisCluster $redis, string $connectionName): void
    {
        if ($redis instanceof LazyObjectInterface && !$redis->isLazyObjectInitialized()) {
            return;
        }

        $this->decorated->keepAlive($redis, $connectionName);
    }
}
