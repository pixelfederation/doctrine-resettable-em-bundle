<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\Redis\Cluster\Connection;

use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\OptimizedRedisClusterAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\RedisClusterAliveKeeper;
use RedisCluster;
use Symfony\Bridge\PhpUnit\ClockMock;

final class OptimizedRedisClusterAliveKeeperTest extends TestCase
{
    /**
     * @group time-sensitive
     */
    public function testKeepAliveEachXSeconds(): void
    {
        ClockMock::register(OptimizedRedisClusterAliveKeeper::class);

        $connectionMock = $this->createStub(RedisCluster::class);
        $connectionName = 'default';
        $decoratedAliveKeeper = $this->createMock(RedisClusterAliveKeeper::class);
        $decoratedAliveKeeper->expects($this->once())
            ->method('keepAlive')
            ->with($connectionMock, $connectionName);

        $aliveKeeper = new OptimizedRedisClusterAliveKeeper($decoratedAliveKeeper, 3);
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
        sleep(2);
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
    }
}
