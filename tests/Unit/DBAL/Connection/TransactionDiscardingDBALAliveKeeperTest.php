<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL\Connection;

use ArrayObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\DBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\TransactionDiscardingDBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Tests\Unit\Helper\ProxyConnectionMock;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class TransactionDiscardingDBALAliveKeeperTest extends TestCase
{
    private const string CONNECTION_NAME = 'default';

    private const string DISCARD_MESSAGE =
        'Connection "default" needed to discard active transaction while running keep-alive routine.';

    private const string ERROR_MESSAGE =
        'An error occurred while discarding active transaction in connection "default".';

    private const string CLOSE_MESSAGE =
        'Connection "default" still believed it was in a transaction after discarding it, so it was closed.';

    public function testRollbackConnectionIfItIsInTransaction(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('error')
            ->with(self::DISCARD_MESSAGE);
        $connectionMock = $this->connectionInTransaction(autoCommit: true, nestingLevels: [1, 0]);
        $connectionMock->expects($this->once())
            ->method('rollBack');
        $connectionMock->expects($this->never())
            ->method('close');

        $this->keepAlive($connectionMock, $loggerMock);
    }

    public function testRollbackAllNestingLevelsWithoutClosingConnection(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('error')
            ->with(self::DISCARD_MESSAGE);
        $connectionMock = $this->connectionInTransaction(autoCommit: true, nestingLevels: [2, 0]);
        $connectionMock->expects($this->exactly(2))
            ->method('rollBack');
        $connectionMock->expects($this->never())
            ->method('close');

        $this->keepAlive($connectionMock, $loggerMock);
    }

    public function testDoNotCloseConnectionWithAutoCommitOffAfterCleanDiscard(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('error')
            ->with(self::DISCARD_MESSAGE);
        // with auto-commit off DBAL begins a new transaction right after the outermost rollback
        $connectionMock = $this->connectionInTransaction(autoCommit: false, nestingLevels: [1, 1]);
        $connectionMock->expects($this->once())
            ->method('rollBack');
        $connectionMock->expects($this->never())
            ->method('close');

        $this->keepAlive($connectionMock, $loggerMock);
    }

    public function testCloseConnectionAndClearRollbackOnlyIfRollbackFails(): void
    {
        $failure = new RuntimeException('SAVEPOINT DOCTRINE_2 does not exist');
        $calls = new ArrayObject();

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->exactly(3))
            ->method('error')
            ->willReturnCallback(static function (string $message, array $context = []) use ($calls): void {
                $calls->append(['error', $message, $context]);
            });

        // leaked at 2 after the failed rollback, then 1 after reconnecting with beginTransaction()
        $connectionMock = $this->connectionInTransaction(autoCommit: true, nestingLevels: [2, 2, 1]);
        $connectionMock->expects($this->exactly(2))
            ->method('rollBack')
            ->willReturnCallback(static function () use ($calls, $failure): void {
                $calls->append(['rollBack']);
                if (count($calls) === 2) {
                    throw $failure;
                }
            });
        $connectionMock->expects($this->once())
            ->method('close')
            ->willReturnCallback(static function () use ($calls): void {
                $calls->append(['close']);
            });
        $connectionMock->expects($this->once())
            ->method('beginTransaction')
            ->willReturnCallback(static function () use ($calls): void {
                $calls->append(['beginTransaction']);
            });

        $this->keepAlive($connectionMock, $loggerMock);

        self::assertSame(
            [
                ['error', self::DISCARD_MESSAGE, []],
                ['rollBack'],
                ['error', self::ERROR_MESSAGE, ['exception' => $failure]],
                ['error', self::CLOSE_MESSAGE, []],
                ['close'],
                ['beginTransaction'],
                ['rollBack'],
            ],
            $calls->getArrayCopy(),
        );
    }

    public function testDoNotRollbackConnectionIfItIsNotInTransaction(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->never())
            ->method('error');
        $connectionMock = $this->createMock(ProxyConnectionMock::class);
        $connectionMock->expects($this->atLeast(1))
            ->method('isTransactionActive')
            ->willReturn(false);
        $connectionMock->expects($this->never())
            ->method('rollBack');
        $connectionMock->expects($this->never())
            ->method('close');

        $this->keepAlive($connectionMock, $loggerMock);
    }

    /**
     * @param list<int> $nestingLevels nesting levels in the order the keeper reads them
     */
    private function connectionInTransaction(bool $autoCommit, array $nestingLevels): ProxyConnectionMock&MockObject
    {
        $connectionMock = $this->createMock(ProxyConnectionMock::class);
        $connectionMock->method('isTransactionActive')
            ->willReturn(true);
        $connectionMock->method('isAutoCommit')
            ->willReturn($autoCommit);
        $connectionMock->method('getTransactionNestingLevel')
            ->willReturnOnConsecutiveCalls(...$nestingLevels);

        return $connectionMock;
    }

    private function keepAlive(ProxyConnectionMock&MockObject $connectionMock, LoggerInterface $loggerMock): void
    {
        $decoratedAliveKeeper = $this->createMock(DBALAliveKeeper::class);
        $decoratedAliveKeeper->expects($this->once())
            ->method('keepAlive')
            ->with($connectionMock, self::CONNECTION_NAME);

        $aliveKeeper = new TransactionDiscardingDBALAliveKeeper($decoratedAliveKeeper, $loggerMock);
        $aliveKeeper->keepAlive($connectionMock, self::CONNECTION_NAME);
    }
}
