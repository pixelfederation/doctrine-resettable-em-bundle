<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL\Connection;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\DBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\PassiveIgnoringDBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Tests\Unit\Helper\ProxyConnectionMock;
use Symfony\Component\VarExporter\LazyObjectInterface;

final class PassiveIgnoringDBALAliveKeeperTest extends TestCase
{
    public function testKeepAliveWithoutInitialisedConnectionProxyDoesNotDoAnything(): void
    {
        $connectionMock = $this->createMock(ProxyConnectionMock::class);
        self::assertInstanceOf(Connection::class, $connectionMock);
        self::assertInstanceOf(LazyObjectInterface::class, $connectionMock);
        $connectionMock->expects($this->atLeast(1))
            ->method('isLazyObjectInitialized')
            ->willReturn(false);
        $connectionMock->expects($this->exactly(0))
            ->method('getDatabasePlatform');
        $connectionName = 'default';

        $decoratedAliveKeeper = $this->createMock(DBALAliveKeeper::class);
        $decoratedAliveKeeper->expects($this->exactly(0))
            ->method('keepAlive')
            ->with($connectionMock, $connectionName);

        $aliveKeeper = new PassiveIgnoringDBALAliveKeeper(
            $decoratedAliveKeeper,
        );
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
    }

    public function testKeepAliveWithoutInitialisedConnectionDoesNotDoAnything(): void
    {
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects($this->atLeast(1))
            ->method('isConnected')
            ->willReturn(false);
        $connectionMock->expects($this->exactly(0))
            ->method('getDatabasePlatform');
        $connectionName = 'default';

        $decoratedAliveKeeper = $this->createMock(DBALAliveKeeper::class);
        $decoratedAliveKeeper->expects($this->exactly(0))
            ->method('keepAlive')
            ->with($connectionMock, $connectionName);

        $aliveKeeper = new PassiveIgnoringDBALAliveKeeper(
            $decoratedAliveKeeper,
        );
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
    }

    public function testKeepAliveWithInitialisedConnectionDelegatesControl(): void
    {
        $connectionMock = $this->createMock(ProxyConnectionMock::class);
        self::assertInstanceOf(LazyObjectInterface::class, $connectionMock);
        self::assertInstanceOf(Connection::class, $connectionMock);
        $connectionMock->expects($this->atLeast(1))
            ->method('isLazyObjectInitialized')
            ->willReturn(true);
        $connectionMock->expects($this->atLeast(1))
            ->method('isConnected')
            ->willReturn(true);
        $connectionMock->expects($this->exactly(0))
            ->method('getDatabasePlatform');
        $connectionName = 'default';

        $decoratedAliveKeeper = $this->createMock(DBALAliveKeeper::class);
        $decoratedAliveKeeper->expects($this->atLeast(1))
            ->method('keepAlive')
            ->with($connectionMock, $connectionName);

        $aliveKeeper = new PassiveIgnoringDBALAliveKeeper($decoratedAliveKeeper);
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
    }
}
