<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection;

use RedisCluster;

interface RedisClusterAliveKeeper
{
    public function keepAlive(RedisCluster $redis, string $connectionName): void;
}
