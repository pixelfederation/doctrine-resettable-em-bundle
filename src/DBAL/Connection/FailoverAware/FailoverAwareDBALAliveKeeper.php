<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\FailoverAware;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Exception;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\DBALAliveKeeper;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class FailoverAwareDBALAliveKeeper implements DBALAliveKeeper
{
    private LoggerInterface $logger;

    private ConnectionType $connectionType;

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct(LoggerInterface $logger, string $connectionType = ConnectionType::WRITER)
    {
        $this->logger = $logger;
        $this->connectionType = ConnectionType::create($connectionType);
    }

    /**
     * @throws Exception
     */
    public function keepAlive(Connection $connection, string $connectionName): void
    {
        try {
            if (!$this->isProperConnection($connection)) {
                $logLevel = $this->connectionType->isWriter() ? LogLevel::ALERT : LogLevel::WARNING;
                $this->logger->log(
                    $logLevel,
                    sprintf("Failover reconnect for connection '%s'", $connectionName)
                );
                $this->reconnect($connection);
            }
        } catch (DriverException $e) {
            $this->logger->info(
                sprintf("Exceptional reconnect for DBAL connection '%s'", $connectionName),
                [
                    'exception' => $e,
                ]
            );

            try {
                $this->reconnect($connection);
            } catch (DriverException $e) {
                // this is usual reconnect
            }
        }
    }

    private function reconnect(Connection $connection): void
    {
        $connection->close();
        /** @psalm-suppress InternalMethod */
        $connection->connect();
    }

    /**
     * returns true if the connection is expected to be writable and innodb_read_only is set to 0
     * or if the connection is not expected to be writable and innodb_read_only is set to 1
     * these flags were only tested on AWS Aurora RDS
     *
     * @throws DriverException
     */
    private function isProperConnection(Connection $connection): bool
    {
        $stmt = $connection->executeQuery('SELECT @@global.innodb_read_only;');
        $currentConnectionIsWriter = (bool)$stmt->fetchOne() === false;

        return $this->connectionType->isWriter() === $currentConnectionIsWriter;
    }
}
