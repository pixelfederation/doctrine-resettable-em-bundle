<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\FailoverAware;

use Doctrine\DBAL\Connection;
use Exception;
use PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper\AliveKeeper;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class FailoverAwareAliveKeeper implements AliveKeeper
{
    private LoggerInterface $logger;

    private Connection $connection;

    private string $connectionName;

    private ConnectionType $connectionType;

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        string $connectionName,
        string $connectionType = ConnectionType::WRITER
    ) {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->connectionName = $connectionName;
        $this->connectionType = ConnectionType::create($connectionType);
    }

    /**
     * @throws Exception
     */
    public function keepAlive(): void
    {
        try {
            if (!$this->isProperConnection()) {
                $logLevel = $this->connectionType->isWriter() ? LogLevel::ALERT : LogLevel::WARNING;
                $this->logger->log(
                    $logLevel,
                    sprintf("Failover reconnect for connection '%s'", $this->connectionName)
                );
                $this->reconnect();
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->logger->info(
                sprintf("Exceptional reconnect for DBAL connection '%s'", $this->connectionName),
                [
                    'exception' => $e,
                ]
            );

            try {
                $this->reconnect();
            } catch (\Doctrine\DBAL\Exception $e) {
                // this is usual reconnect
            }
        }
    }

    /**
     *
     */
    private function reconnect(): void
    {
        $this->connection->close();
        $this->connection->connect();
    }

    /**
     * returns true if the connection is expected to be writable and innodb_read_only is set to 0
     * or if the connection is not expected to be writable and innodb_read_only is set to 1
     * these flags were only tested on AWS Aurora RDS
     * @throws \Doctrine\DBAL\Exception
     */
    private function isProperConnection(): bool
    {
        $stmt = $this->connection->executeQuery('SELECT @@global.innodb_read_only;');
        $currentConnectionIsWriter = ((bool)$stmt->fetchOne()) === false;

        return $this->connectionType->isWriter() === $currentConnectionIsWriter;
    }
}
