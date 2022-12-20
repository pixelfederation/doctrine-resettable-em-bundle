<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Redis\Cluster\Connection;

use PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper\AliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\PassiveIgnoringRedisClusterAliveKeeper;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class PassiveIgnoringRedisClusterAliveKeeperTest extends TestCase
{
    use ProphecyTrait;

    public function testKeepAliveWithoutInitialisedConnectionProxyDoesNotDoAnything(): void
    {
        $clusterProphecy = $this->prophesize(RedisClusterSpy::class);
        $clusterProphecy->isProxyInitialized()->willReturn(false)->shouldBeCalled();
        /** @var $decoratedAliveKeeper AliveKeeper|ObjectProphecy */
        $decoratedAliveKeeper = $this->prophesize(AliveKeeper::class);
        $decoratedAliveKeeper->keepAlive()->shouldNotBeCalled();

        $aliveKeeper = new PassiveIgnoringRedisClusterAliveKeeper(
            $decoratedAliveKeeper->reveal(),
            $clusterProphecy->reveal()
        );
        $aliveKeeper->keepAlive();
    }

    /**
     * @throws Exception
     */
    public function testKeepAliveWithInitialisedConnectionDelegatesControl(): void
    {
        $clusterProphecy = $this->prophesize(RedisClusterSpy::class);
        $clusterProphecy->isProxyInitialized()->willReturn(true)->shouldBeCalled();
        /** @var $decoratedAliveKeeper AliveKeeper|ObjectProphecy */
        $decoratedAliveKeeper = $this->prophesize(AliveKeeper::class);
        $decoratedAliveKeeper->keepAlive()->shouldBeCalled();

        $aliveKeeper = new PassiveIgnoringRedisClusterAliveKeeper(
            $decoratedAliveKeeper->reveal(),
            $clusterProphecy->reveal()
        );
        $aliveKeeper->keepAlive();
    }
}
