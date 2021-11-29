<?php

declare(strict_types=1);

/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Redis\Cluster\Connection;

use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\RedisClusterAliveKeeper;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use RedisCluster;
use RedisClusterException;

class RedisClusterAliveKeeperTest extends TestCase
{
    use ProphecyTrait;

    public function testKeepAliveWriterWithoutReconnect(): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $clusterProphecy = $this->prophesize(RedisCluster::class);
        $clusterProphecy->ping('hello')->willReturn('hello')->shouldBeCalled();
        $aliveKeeper = new RedisClusterAliveKeeper(
            'default',
            $clusterProphecy->reveal(),
            [],
            $loggerProphecy->reveal(),

        );
        $aliveKeeper->keepAlive();
    }

    /**
     * @throws Exception
     */
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

        $aliveKeeper = new RedisClusterAliveKeeper(
            'default',
            $clusterSpy,
            $constructorParameters,
            $loggerProphecy->reveal(),
        );
        $aliveKeeper->keepAlive();

        self::assertTrue($clusterSpy->wasConstructorCalled());
        self::assertSame($constructorParameters, $clusterSpy->getConstructorParametersSecond());
    }
}
