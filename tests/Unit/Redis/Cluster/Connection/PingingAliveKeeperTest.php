<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\Redis\Cluster\Connection;

use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\PingingAliveKeeper;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use RedisCluster;

class PingingAliveKeeperTest extends TestCase
{
    use ProphecyTrait;

    public function testKeepAliveWriterWithoutReconnect(): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $clusterProphecy = $this->prophesize(RedisCluster::class);
        $clusterProphecy->ping('hello')->willReturn('hello')->shouldBeCalled();
        $aliveKeeper = new PingingAliveKeeper([], $loggerProphecy->reveal());
        $aliveKeeper->keepAlive($clusterProphecy->reveal(), 'default');
    }

    public function testKeepAliveWithReconnectOnFailedPing(): void
    {
        $constructorParameters = [
            'session',
            ['localhost:6379'],
            2,
            2,
        ];

        $clusterSpy = new RedisClusterSpy(...$constructorParameters);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->info("Exceptional reconnect for redis cluster connection 'default'", Argument::any())
            ->shouldBeCalled();

        $aliveKeeper = new PingingAliveKeeper($constructorParameters, $loggerProphecy->reveal());
        $aliveKeeper->keepAlive($clusterSpy, 'default');

        self::assertTrue($clusterSpy->wasConstructorCalled());
        self::assertSame($constructorParameters, $clusterSpy->getConstructorParametersSecond());
    }
}
