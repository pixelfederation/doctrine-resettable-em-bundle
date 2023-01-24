<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\Redis\Cluster\Connection;

use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\RedisClusterAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\RedisClusterPlatformAliveKeeper;
use PHPUnit\Framework\TestCase;
use RedisCluster;

class RedisClusterPlatformAliveKeeperTest extends TestCase
{
    public function testKeepAlive()
    {
        $cName1 = 'default';
        $cMock1 = $this->prophesize(RedisCluster::class);
        $cMock1 = $cMock1->reveal();
        $cName2 = 'other';
        $cMock2 = $this->prophesize(RedisCluster::class);
        $cMock2 = $cMock2->reveal();

        $keeper1 = $this->prophesize(RedisClusterAliveKeeper::class);
        $keeper1->keepAlive($cMock1, $cName1)->shouldBeCalledOnce();
        $keeper2 = $this->prophesize(RedisClusterAliveKeeper::class);
        $keeper2->keepAlive($cMock2, $cName2)->shouldBeCalledOnce();

        $platformKeeper = new RedisClusterPlatformAliveKeeper(
            [
                $cName1 => $cMock1,
                $cName2 => $cMock2,
            ],
            [
                $cName1 => $keeper1->reveal(),
                $cName2 => $keeper2->reveal(),
            ]
        );
        $platformKeeper->keepAlive();
    }
}
