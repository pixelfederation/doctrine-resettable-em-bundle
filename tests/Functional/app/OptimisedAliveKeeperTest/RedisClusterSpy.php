<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\OptimisedAliveKeeperTest;

use RedisCluster;

class RedisClusterSpy extends RedisCluster
{
    private int $constructorCalls = 0;

    private int $pingCount = 0;

    private array $constructorParametersSecond = [];
    public function __construct($name, $seeds, $timeout = null, $readTimeout = null, $persistent = false, $auth = null)
    {
        $this->constructorCalls++;
    }

    public function getConstructorCalls(): int
    {
        return $this->constructorCalls;
    }

    public function ping($nodeParams)
    {
        $this->pingCount++;
    }

    public function getPingCount(): int
    {
        return $this->pingCount;
    }
}
