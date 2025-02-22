<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Exception;
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
     * @throws Exception
     */
    public function keepAlive(Connection $connection, string $connectionName): void
    {
        // roll back unfinished transaction from previous request
        if ($connection->isTransactionActive()) {
            try {
                $this->logger->error(
                    sprintf(
                        'Connection "%s" needed to discard active transaction while running keep-alive routine.',
                        $connectionName,
                    ),
                );
                $connection->rollBack();
            } catch (Throwable $e) {
                $this->logger->error(
                    sprintf(
                        'An error occurred while discarding active transaction in connection "%s".',
                        $connectionName,
                    ),
                    ['exception' => $e],
                );
            }
        }

        $this->decorated->keepAlive($connection, $connectionName);
    }
}
