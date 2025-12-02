<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL\Connection\FailoverAware;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\FailoverAware\ConnectionType;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\FailoverAware\FailoverAwareDBALAliveKeeper;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class FailoverAwareDBALAliveKeeperTest extends TestCase
{
    public function testKeepAliveWriterWithoutReconnect(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $statementMock = $this->createMock(Result::class);
        $statementMock->expects($this->atLeast(1))
            ->method('fetchOne')
            ->willReturn('0');

        $connectionMock = $this->createMock(Connection::class);
        self::assertInstanceOf(Connection::class, $connectionMock);
        $connectionMock->expects($this->atLeast(1))
            ->method('executeQuery')
            ->withAnyParameters()
            ->willReturn($statementMock);
        $connectionMock->expects($this->exactly(0))->method('close');
        $connectionMock->expects($this->exactly(0))->method('getNativeConnection');

        $aliveKeeper = new FailoverAwareDBALAliveKeeper($loggerMock);
        $aliveKeeper->keepAlive($connectionMock, 'default');
    }

    public function testKeepAliveReaderWithoutReconnect(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $statementMock = $this->createMock(Result::class);
        $statementMock->expects($this->atLeast(1))
            ->method('fetchOne')
            ->willReturn('1');

        $connectionMock = $this->createMock(Connection::class);
        self::assertInstanceOf(Connection::class, $connectionMock);
        $connectionMock->expects($this->atLeast(1))
            ->method('executeQuery')
            ->withAnyParameters()
            ->willReturn($statementMock);
        $connectionMock->expects($this->exactly(0))
            ->method('close');
        $connectionMock->expects($this->exactly(0))
            ->method('getNativeConnection');

        $aliveKeeper = new FailoverAwareDBALAliveKeeper($loggerMock, ConnectionType::READER);
        $aliveKeeper->keepAlive($connectionMock, 'default',);
    }

    public function testKeepAliveWriterWithReconnectOnFailover(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->atLeast(1))
            ->method('log')
            ->with(LogLevel::ALERT);
        $statementMock = $this->createMock(Result::class);
        $statementMock->expects($this->atLeast(1))
            ->method('fetchOne')
            ->willReturn('1');

        $connectionMock = $this->createMock(Connection::class);
        self::assertInstanceOf(Connection::class, $connectionMock);
        $connectionMock->expects($this->atLeast(1))
            ->method('executeQuery')
            ->withAnyParameters()
            ->willReturn($statementMock);
        $connectionMock->expects($this->once())
            ->method('close');
        $connectionMock->expects($this->atLeast(1))
            ->method('getNativeConnection');

        $aliveKeeper = new FailoverAwareDBALAliveKeeper($loggerMock);
        $aliveKeeper->keepAlive($connectionMock, 'default');
    }

    public function testKeepAliveReaderWithReconnectOnFailover(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->atLeast(1))
            ->method('log')
            ->with(LogLevel::WARNING);
        $statementMock = $this->createMock(Result::class);
        $statementMock->expects($this->atLeast(1))
            ->method('fetchOne')
            ->willReturn('0');

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects($this->atLeast(1))
            ->method('executeQuery')
            ->withAnyParameters()
            ->willReturn($statementMock);
        $connectionMock->expects($this->once())
            ->method('close');
        $connectionMock->expects($this->atLeast(1))
            ->method('getNativeConnection');

        $aliveKeeper = new FailoverAwareDBALAliveKeeper($loggerMock, ConnectionType::READER);
        $aliveKeeper->keepAlive($connectionMock, 'default');
    }

    public function testKeepAliveWithReconnectConnectionError(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->atLeast(1))
            ->method('info')
            ->withAnyParameters();
        $statementMock = $this->createMock(Result::class);
        $statementMock->expects($this->atLeast(1))
            ->method('fetchOne')
            ->willThrowException($this->createMock(DriverException::class));

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects($this->atLeast(1))
            ->method('executeQuery')
            ->withAnyParameters()
            ->willReturn($statementMock);
        $connectionMock->expects($this->once())
            ->method('close');
        $connectionMock->expects($this->atLeast(1))
            ->method('getNativeConnection');

        $aliveKeeper = new FailoverAwareDBALAliveKeeper($loggerMock);
        $aliveKeeper->keepAlive($connectionMock, 'default');
    }
}
