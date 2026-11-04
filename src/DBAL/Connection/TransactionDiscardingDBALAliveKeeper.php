<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Override;
use Psr\Log\LoggerInterface;
use Throwable;

final class TransactionDiscardingDBALAliveKeeper implements DBALAliveKeeper
{
    public function __construct(
        private readonly DBALAliveKeeper $decorated,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Discards whatever transaction a previous request or message left open, all of its nesting levels.
     *
     * A connection can believe it is inside a transaction the server no longer has: MySQL answers a
     * deadlock by rolling the whole transaction back, savepoints included, and when that happens inside a
     * nested transaction DBAL's rollback to the savepoint fails and its nesting level is never lowered.
     * No rollback can undo that, since each one is refused the same way. Closing the connection is what
     * makes DBAL forget its nesting level; the next query reconnects.
     */
    #[Override]
    public function keepAlive(Connection $connection, string $connectionName): void
    {
        if ($connection->isTransactionActive()) {
            $this->logger->error(
                sprintf(
                    'Connection "%s" needed to discard active transaction while running keep-alive routine.',
                    $connectionName,
                ),
            );
            $this->discardTransaction($connection, $connectionName);
        }

        $this->decorated->keepAlive($connection, $connectionName);
    }

    private function discardTransaction(Connection $connection, string $connectionName): void
    {
        try {
            for ($level = $connection->getTransactionNestingLevel(); $level > 0; --$level) {
                $connection->rollBack();
            }
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf('An error occurred while discarding active transaction in connection "%s".', $connectionName),
                ['exception' => $e],
            );
        }

        // With auto-commit off, DBAL begins a new transaction as soon as the last one is rolled back, so a
        // connection that was discarded cleanly is one level deep rather than none.
        if ($connection->getTransactionNestingLevel() <= ($connection->isAutoCommit() ? 0 : 1)) {
            return;
        }

        $this->logger->error(
            sprintf(
                'Connection "%s" still believed it was in a transaction after discarding it, so it was closed.',
                $connectionName,
            ),
        );
        $connection->close();
        $this->clearRollbackOnly($connection, $connectionName);
    }

    /**
     * close() lowers the nesting level but leaves a rollback-only mark in place, and only a rollback at the
     * outermost level clears it: without this, the next transaction's commit would fail once.
     */
    private function clearRollbackOnly(Connection $connection, string $connectionName): void
    {
        try {
            $connection->beginTransaction();
            $connection->rollBack();
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf('An error occurred while discarding active transaction in connection "%s".', $connectionName),
                ['exception' => $e],
            );
        }
    }
}
