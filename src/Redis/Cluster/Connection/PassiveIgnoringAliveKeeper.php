<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection;

use ProxyManager\Proxy\VirtualProxyInterface;
use RedisCluster;

final class PassiveIgnoringAliveKeeper implements AliveKeeper
{
    private AliveKeeper $decorated;

    public function __construct(AliveKeeper $decorated)
    {
        $this->decorated = $decorated;
    }

    public function keepAlive(RedisCluster $redis, string $connectionName): void
    {
        if ($redis instanceof VirtualProxyInterface && !$redis->isProxyInitialized()) {
            return;
        }

        $this->decorated->keepAlive($redis, $connectionName);
    }
}
