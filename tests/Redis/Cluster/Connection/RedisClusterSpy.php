<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Redis\Cluster\Connection;

use RedisCluster;
use RedisClusterException;

class RedisClusterSpy extends RedisCluster
{
    private int $constructorCalls = 0;

    private bool $wasConstructorCalled = false;

    private array $constructorParametersFirst = [];

    private array $constructorParametersSecond = [];

    public function __construct($name, $seeds, $timeout = null, $readTimeout = null, $persistent = false, $auth = null)
    {
        $this->constructorCalls++;

        if ($this->constructorCalls === 1) {
            $this->constructorParametersFirst = [$name, $seeds, $timeout, $readTimeout];
        } elseif ($this->constructorCalls > 1) {
            $this->wasConstructorCalled = true;
            $this->constructorParametersSecond = [$name, $seeds, $timeout, $readTimeout];
        }
    }

    public function wasConstructorCalled(): bool
    {
        return $this->wasConstructorCalled;
    }

    public function getConstructorParametersFirst(): array
    {
        return $this->constructorParametersFirst;
    }

    public function getConstructorParametersSecond(): array
    {
        return $this->constructorParametersSecond;
    }

    public function ping($nodeParams)
    {
        throw new RedisClusterException('Test exception');
    }
}
