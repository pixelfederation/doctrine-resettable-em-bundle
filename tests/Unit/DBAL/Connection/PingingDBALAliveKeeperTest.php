<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionLost;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\PingingDBALAliveKeeper;
use ReflectionClass;

final class PingingDBALAliveKeeperTest extends TestCase
{
    public function testKeepAliveWithoutReconnect(): void
    {
        $query = 'SELECT 1';
        $platformMock = $this->createMock(AbstractPlatform::class);
        $platformMock->expects($this->atLeast(1))
            ->method('getDummySelectSQL')
            ->willReturn($query);
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects($this->atLeast(1))
            ->method('getDatabasePlatform')
            ->willReturn($platformMock);
        $connectionMock->expects($this->atLeast(1))
            ->method('executeQuery')
            ->with($query);
        $connectionMock->expects($this->exactly(0))
            ->method('close');
        $connectionMock->expects($this->exactly(0))
            ->method('getNativeConnection');

        $aliveKeeper = new PingingDBALAliveKeeper();
        $aliveKeeper->keepAlive($connectionMock, 'default');
    }

    public function testKeepAliveWithReconnectOnFailedPing(): void
    {
        $query = 'SELECT 1';
        $platformMock = $this->createMock(AbstractPlatform::class);
        $platformMock->expects($this->atLeast(1))
            ->method('getDummySelectSQL')
            ->willReturn($query);
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects($this->atLeast(1))
            ->method('getDatabasePlatform')
            ->willReturn($platformMock);
        $connLostRefl = new ReflectionClass(ConnectionLost::class);
        $connectionMock->expects($this->once())
            ->method('executeQuery')
            ->with($query)
            ->willThrowException($connLostRefl->newInstanceWithoutConstructor());
        $connectionMock->expects($this->atLeast(1))
            ->method('close');
        $connectionMock->expects($this->atLeast(1))
            ->method('getNativeConnection')
            ->willReturn(true);

        $aliveKeeper = new PingingDBALAliveKeeper();
        $aliveKeeper->keepAlive($connectionMock, 'default');
    }
}
