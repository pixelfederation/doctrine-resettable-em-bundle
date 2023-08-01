<?php
declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\Redis\Cluster\Connection;

use Exception;
use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\RedisClusterAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\OptimizedRedisClusterAliveKeeper;
use RedisCluster;
use Symfony\Bridge\PhpUnit\ClockMock;

class OptimizedRedisClusterAliveKeeperTest extends TestCase
{
    /**
     * @group time-sensitive
     */
    public function testKeepAliveEachXSeconds(): void
    {
        ClockMock::register(OptimizedRedisClusterAliveKeeper::class);

        $connectionMock = $this->createMock(RedisCluster::class);
        $connectionName = 'default';
        $decoratedAliveKeepr = $this->createMock(RedisClusterAliveKeeper::class);
        $decoratedAliveKeepr->expects(self::once())
            ->method('keepAlive')
            ->with($connectionMock, $connectionName);

        $aliveKeeper = new OptimizedRedisClusterAliveKeeper($decoratedAliveKeepr, 3);
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
        sleep(2);
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
    }
}
