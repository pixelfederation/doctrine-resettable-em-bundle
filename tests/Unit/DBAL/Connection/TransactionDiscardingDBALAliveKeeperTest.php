<?php
declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL\Connection;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\DBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\TransactionDiscardingDBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Tests\Unit\Helper\ProxyConnectionMock;
use ProxyManager\Proxy\VirtualProxyInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class TransactionDiscardingDBALAliveKeeperTest extends TestCase
{
    public function testRollbackConnectionIfItIsInTransaction(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects(self::atLeast(1))
            ->method('error')
            ->with('Connection "default" needed to discard active transaction while running keep-alive routine.');
        $connectionMock = $this->createMock(ProxyConnectionMock::class);
        $connectionMock->expects(self::atLeast(1))
            ->method('isTransactionActive')
            ->willReturn(true);
        $connectionMock->expects(self::atLeast(1))
            ->method('rollBack');
        $connectionName = 'default';

        $decoratedAliveKeeper = $this->createMock(DBALAliveKeeper::class);
        $decoratedAliveKeeper->expects(self::atLeast(1))
            ->method('keepAlive')
            ->with($connectionMock, $connectionName);

        $aliveKeeper = new TransactionDiscardingDBALAliveKeeper($decoratedAliveKeeper, $loggerMock);
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
    }

    public function testRollbackConnectionIfItIsInTransactionAndLogRollbackException(): void
    {
        $connectionName = 'default';
        $exceptionMock = $this->createMock(Throwable::class);

        $matcher = self::exactly(2);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($matcher)
            ->method('error')
            ->with(
                $this->callback(function ($value) use ($matcher, $connectionName) {
                    match ($matcher->numberOfInvocations()) {
                        1 => $this->assertEquals(
                            sprintf(
                                'Connection "%s" needed to discard active transaction while running keep-alive routine.',
                                $connectionName
                            ),
                            $value
                        ),
                        2 => $this->assertEquals(
                            sprintf('An error occurred while discarding active transaction in connection "%s".', $connectionName),
                            $value
                        ),
                    };

                    return true;
                }),
                $this->callback(function ($value) use ($matcher, $exceptionMock) {
                    match ($matcher->numberOfInvocations()) {
                        1 => true,
                        2 => self::assertEquals($value, ['exception' => $exceptionMock]),
                    };

                    return true;
                })
        );

        $connectionMock = $this->createMock(ProxyConnectionMock::class);
        $connectionMock->expects(self::atLeast(1))
            ->method('isTransactionActive')
            ->willReturn(true);
        $connectionMock->expects(self::atLeast(1))
            ->method('rollBack')
            ->willThrowException($exceptionMock);

        $decoratedAliveKeeper = $this->createMock(DBALAliveKeeper::class);
        $decoratedAliveKeeper->expects(self::atLeast(1))
            ->method('keepAlive')
            ->with($connectionMock, $connectionName);

        $aliveKeeper = new TransactionDiscardingDBALAliveKeeper($decoratedAliveKeeper, $loggerMock);
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
    }

    public function testDoNotRollbackConnectionIfItIsNotInTransaction(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects(self::exactly(0))
            ->method('error')
            ->withAnyParameters();
        ;
        $connectionMock = $this->createMock(ProxyConnectionMock::class);
        $connectionMock->expects(self::atLeast(1))
            ->method('isTransactionActive')
            ->willReturn(false);
        $connectionMock->expects(self::exactly(0))
            ->method('rollBack');

        $connectionName = 'default';

        $decoratedAliveKeeper = $this->createMock(DBALAliveKeeper::class);
        $decoratedAliveKeeper->expects(self::atLeast(1))
            ->method('keepAlive')
            ->with($connectionMock, $connectionName);

        $aliveKeeper = new TransactionDiscardingDBALAliveKeeper($decoratedAliveKeeper, $loggerMock);
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
    }
}
