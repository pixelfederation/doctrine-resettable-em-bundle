<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Exception;
use PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper\AliveKeeper;
use Psr\Log\LoggerInterface;
use Throwable;

final class TransactionDiscardingDBALAliveKeeper implements AliveKeeper
{
    private AliveKeeper $decorated;

    private Connection $connection;

    private string $connectionName;

    private LoggerInterface $logger;

    public function __construct(
        AliveKeeper $decorated,
        Connection $connection,
        string $connectionName,
        LoggerInterface $logger
    ) {
        $this->decorated = $decorated;
        $this->connection = $connection;
        $this->connectionName = $connectionName;
        $this->logger = $logger;
    }

    /**
     * @throws Exception
     */
    public function keepAlive(): void
    {
        // roll back unfinished transaction from previous request
        if ($this->connection->isTransactionActive()) {
            try {
                $this->connection->rollBack();
            } catch (Throwable $e) {
                $this->logger->error(
                    sprintf(
                        'An error occurred while discarding active transaction in connection "%s".',
                        $this->connectionName
                    ),
                    [
                        'exception' => $e,
                    ]
                );
            } finally {
                $this->logger->error(
                    sprintf(
                        'Connection "%s" needed to discard active transaction while running keep-alive routine.',
                        $this->connectionName
                    )
                );
            }
        }

        $this->decorated->keepAlive();
    }
}
