<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Exception;
use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\AliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\OptimizedAliveKeeper;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bridge\PhpUnit\ClockMock;

class OptimizedAliveKeeperTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @throws Exception
     * @group time-sensitive
     */
    public function testKeepAliveEachXSeconds(): void
    {
        ClockMock::register(OptimizedAliveKeeper::class);

        $connectionMock = $this->prophesize(Connection::class)->reveal();
        $connectionName = 'default';
        $decoratedAliveKeepr = $this->prophesize(AliveKeeper::class);
        $decoratedAliveKeepr->keepAlive($connectionMock, $connectionName)->shouldBeCalledOnce();

        $aliveKeeper = new OptimizedAliveKeeper($decoratedAliveKeepr->reveal(), 3);
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
        sleep(2);
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
    }
}
