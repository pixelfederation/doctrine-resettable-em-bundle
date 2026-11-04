<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\DBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\TransactionDiscardingDBALAliveKeeper;
use Psr\Log\NullLogger;

/**
 * Runs the keeper against a real DBAL connection whose savepoints were dropped behind its back, the way MySQL
 * drops them when it rolls a deadlocked transaction back.
 */
final class TransactionDiscardingDBALAliveKeeperRealConnectionTest extends TestCase
{
    /**
     * @return iterable<string, array{bool, int}>
     */
    public static function autoCommitModes(): iterable
    {
        yield 'auto-commit on' => [true, 0];
        yield 'auto-commit off' => [false, 1];
    }

    #[DataProvider('autoCommitModes')]
    public function testLeakedNestingLevelIsDiscardedAndRollbackOnlyCleared(
        bool $autoCommit,
        int $expectedNestingLevel,
    ): void {
        $connection = $this->leakedConnection($autoCommit);

        $this->keepAlive($connection);

        self::assertSame($expectedNestingLevel, $connection->getTransactionNestingLevel());

        $connection->beginTransaction();
        self::assertFalse($connection->isRollbackOnly());
        $connection->commit();
    }

    /**
     * Opens an outer and a nested transaction, marks it rollback-only and then drops the transaction natively,
     * so DBAL keeps believing it is two levels deep and its savepoint rollback is refused.
     */
    private function leakedConnection(bool $autoCommit): Connection
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $connection->setAutoCommit($autoCommit);
        $connection->beginTransaction();
        if ($autoCommit) {
            $connection->beginTransaction();
        }
        $connection->setRollbackOnly();

        $nativeConnection = $connection->getNativeConnection();
        self::assertInstanceOf(PDO::class, $nativeConnection);
        $nativeConnection->exec('ROLLBACK');
        self::assertSame(2, $connection->getTransactionNestingLevel());

        return $connection;
    }

    private function keepAlive(Connection $connection): void
    {
        $decorated = self::createStub(DBALAliveKeeper::class);
        $aliveKeeper = new TransactionDiscardingDBALAliveKeeper($decorated, new NullLogger());
        $aliveKeeper->keepAlive($connection, 'default');
    }
}
