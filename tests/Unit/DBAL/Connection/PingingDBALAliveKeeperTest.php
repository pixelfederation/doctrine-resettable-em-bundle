<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionLost;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Exception;
use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\PingingDBALAliveKeeper;
use ReflectionClass;

class PingingDBALAliveKeeperTest extends TestCase
{
    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testKeepAliveWithoutReconnect(): void
    {
        $query = 'SELECT 1';
        $platformMock = $this->createMock(AbstractPlatform::class);
        $platformMock->expects(self::atLeast(1))
            ->method('getDummySelectSQL')
            ->willReturn($query);
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects(self::atLeast(1))
            ->method('getDatabasePlatform')
            ->willReturn($platformMock);
        $connectionMock->expects(self::atLeast(1))
            ->method('executeQuery')
            ->with($query);
        $connectionMock->expects(self::exactly(0))
            ->method('close');
        $connectionMock->expects(self::exactly(0))
            ->method('connect');

        $aliveKeeper = new PingingDBALAliveKeeper();
        $aliveKeeper->keepAlive($connectionMock, 'default');
    }

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testKeepAliveWithReconnectOnFailedPing(): void
    {
        $query = 'SELECT 1';
        $platformMock = $this->createMock(AbstractPlatform::class);
        $platformMock->expects(self::atLeast(1))
            ->method('getDummySelectSQL')
            ->willReturn($query);
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects(self::atLeast(1))
            ->method('getDatabasePlatform')
            ->willReturn($platformMock);
        $connLostRefl = new ReflectionClass(ConnectionLost::class);
        $connectionMock->expects(self::once())
            ->method('executeQuery')
            ->with($query)
            ->willThrowException($connLostRefl->newInstanceWithoutConstructor());
        $connectionMock->expects(self::atLeast(1))
            ->method('close');
        $connectionMock->expects(self::atLeast(1))
            ->method('connect')
            ->willReturn(true);

        $aliveKeeper = new PingingDBALAliveKeeper();
        $aliveKeeper->keepAlive($connectionMock, 'default');
    }
}
