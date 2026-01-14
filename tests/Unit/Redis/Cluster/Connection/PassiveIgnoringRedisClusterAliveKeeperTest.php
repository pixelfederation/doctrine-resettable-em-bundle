<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\Redis\Cluster\Connection;

use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\PassiveIgnoringRedisClusterAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\RedisClusterAliveKeeper;

final class PassiveIgnoringRedisClusterAliveKeeperTest extends TestCase
{
    public function testKeepAliveWithoutInitialisedConnectionProxyDoesNotDoAnything(): void
    {
        $clusterMock = $this->createMock(RedisClusterSpy::class);
        $clusterMock->expects(self::atLeast(1))
            ->method('isLazyObjectInitialized')
            ->willReturn(false);
        $connectionName = 'default';
        $decoratedAliveKeeper = $this->createMock(RedisClusterAliveKeeper::class);
        $decoratedAliveKeeper->expects(self::exactly(0))
            ->method('keepAlive')
            ->with($clusterMock, $connectionName);

        $aliveKeeper = new PassiveIgnoringRedisClusterAliveKeeper($decoratedAliveKeeper);
        $aliveKeeper->keepAlive($clusterMock, $connectionName);
    }

    public function testKeepAliveWithInitialisedConnectionDelegatesControl(): void
    {
        $clusterMock = $this->createMock(RedisClusterSpy::class);
        $clusterMock->expects(self::atLeast(1))
            ->method('isLazyObjectInitialized')
            ->willReturn(true);
        $connectionName = 'default';
        $decoratedAliveKeeper = $this->createMock(RedisClusterAliveKeeper::class);
        $decoratedAliveKeeper->expects(self::atLeast(1))
            ->method('keepAlive')
            ->with($clusterMock, $connectionName);

        $aliveKeeper = new PassiveIgnoringRedisClusterAliveKeeper($decoratedAliveKeeper);
        $aliveKeeper->keepAlive($clusterMock, $connectionName);
    }
}
