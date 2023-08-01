<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL\Connection;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\DBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\OptimizedDBALAliveKeeper;
use Symfony\Bridge\PhpUnit\ClockMock;

class OptimizedDBALAliveKeeperTest extends TestCase
{
    /**
     * @group time-sensitive
     */
    public function testKeepAliveEachXSeconds(): void
    {
        ClockMock::register(OptimizedDBALAliveKeeper::class);

        $connectionMock = $this->createMock(Connection::class);
        $connectionName = 'default';
        $decoratedAliveKeepr = $this->createMock(DBALAliveKeeper::class);
        $decoratedAliveKeepr->expects(self::once())
            ->method('keepAlive')
            ->with($connectionMock, $connectionName);

        $aliveKeeper = new OptimizedDBALAliveKeeper($decoratedAliveKeepr, 3);
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
        sleep(2);
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
    }
}
