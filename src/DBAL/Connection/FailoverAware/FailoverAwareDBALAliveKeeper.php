<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\FailoverAware;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Override;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\DBALAliveKeeper;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class FailoverAwareDBALAliveKeeper implements DBALAliveKeeper
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ConnectionType $connectionType = ConnectionType::WRITER,
    ) {
    }

    #[Override]
    // phpcs:ignore SlevomatCodingStandard.Complexity.Cognitive.ComplexityTooHigh
    public function keepAlive(Connection $connection, string $connectionName): void
    {
        try {
            if (!$this->isProperConnection($connection)) {
                $logLevel = $this->connectionType === ConnectionType::WRITER ? LogLevel::ALERT : LogLevel::WARNING;
                $this->logger->log(
                    $logLevel,
                    sprintf("Failover reconnect for connection '%s'", $connectionName),
                );
                $this->reconnect($connection);
            }
        } catch (DriverException $e) {
            $this->logger->info(
                sprintf("Exceptional reconnect for DBAL connection '%s'", $connectionName),
                ['exception' => $e],
            );

            try {
                $this->reconnect($connection);
            } catch (DriverException) {
                // this is usual reconnect
            }
        }
    }

    private function reconnect(Connection $connection): void
    {
        $connection->close();
        $connection->getNativeConnection();
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
        /** @var mixed $result */
        $result = $stmt->fetchOne();
        $isReadOnly = $result === 1 || $result === '1';

        return ($this->connectionType === ConnectionType::WRITER) !== $isReadOnly;
    }
}
