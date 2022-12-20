<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection;

use PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper\AliveKeeper;
use ProxyManager\Proxy\VirtualProxyInterface;
use RedisCluster;

final class PassiveIgnoringRedisClusterAliveKeeper implements AliveKeeper
{
    private AliveKeeper $decorated;

    private RedisCluster $redis;

    public function __construct(AliveKeeper $decorated, RedisCluster $redis)
    {
        $this->decorated = $decorated;
        $this->redis = $redis;
    }

    public function keepAlive(): void
    {
        if ($this->redis instanceof VirtualProxyInterface && !$this->redis->isProxyInitialized()) {
            return;
        }

        $this->decorated->keepAlive();
    }
}
