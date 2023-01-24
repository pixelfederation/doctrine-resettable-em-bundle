<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\Redis\Cluster\Connection;

use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\RedisClusterAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\PassiveIgnoringRedisClusterAliveKeeper;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class PassiveIgnoringRedisClusterAliveKeeperTest extends TestCase
{
    use ProphecyTrait;

    public function testKeepAliveWithoutInitialisedConnectionProxyDoesNotDoAnything(): void
    {
        $clusterProphecy = $this->prophesize(RedisClusterSpy::class);
        $clusterProphecy->isProxyInitialized()->willReturn(false)->shouldBeCalled();
        $clusterMock = $clusterProphecy->reveal();
        $connectionName = 'default';
        /** @var $decoratedAliveKeeper RedisClusterAliveKeeper|ObjectProphecy */
        $decoratedAliveKeeper = $this->prophesize(RedisClusterAliveKeeper::class);
        $decoratedAliveKeeper->keepAlive($clusterMock, $connectionName)->shouldNotBeCalled();

        $aliveKeeper = new PassiveIgnoringRedisClusterAliveKeeper($decoratedAliveKeeper->reveal());
        $aliveKeeper->keepAlive($clusterMock, $connectionName);
    }

    public function testKeepAliveWithInitialisedConnectionDelegatesControl(): void
    {
        $clusterProphecy = $this->prophesize(RedisClusterSpy::class);
        $clusterProphecy->isProxyInitialized()->willReturn(true)->shouldBeCalled();
        $clusterMock = $clusterProphecy->reveal();
        $connectionName = 'default';
        /** @var $decoratedAliveKeeper RedisClusterAliveKeeper|ObjectProphecy */
        $decoratedAliveKeeper = $this->prophesize(RedisClusterAliveKeeper::class);
        $decoratedAliveKeeper->keepAlive($clusterMock, $connectionName)->shouldBeCalled();

        $aliveKeeper = new PassiveIgnoringRedisClusterAliveKeeper($decoratedAliveKeeper->reveal());
        $aliveKeeper->keepAlive($clusterMock, $connectionName);
    }
}
