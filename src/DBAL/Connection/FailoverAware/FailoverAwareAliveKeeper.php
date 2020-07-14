<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\FailoverAware;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Exception;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\AliveKeeper;
use Psr\Log\LoggerInterface;

/**
 */
final class FailoverAwareAliveKeeper implements AliveKeeper
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $conntectionName;

    /**
     * @var ConnectionType
     */
    private $connectionType;

    /**
     * @param LoggerInterface $logger
     * @param Connection      $connection
     * @param string          $connectionName
     * @param string          $connectionType
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
        $this->conntectionName = $connectionName;
        $this->connectionType = ConnectionType::create($connectionType);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function keepAlive(): void
    {
        try {
            if (!$this->isProperConnection()) {
                $logLevel = $this->connectionType->isWriter() ? 'alert' : 'warning';
                $this->logger->{$logLevel}(sprintf('Failover reconnect for connection \'%s\'', $this->conntectionName));
                $this->reconnect();
            }
        } catch (DBALException $e) {
            $this->logger->info(
                sprintf('Exceptional reconnect for connection \'%s\'', $this->conntectionName),
                [
                    'exception' => $e,
                ]
            );
            $this->reconnect();
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
     * @return bool
     * @throws DBALException
     */
    private function isProperConnection(): bool
    {
        $stmt = $this->connection->query('SELECT @@global.innodb_read_only;');
        $currentConnectionIsWriter = ((bool)$stmt->fetchColumn(0)) === false;

        return $this->connectionType->isWriter() === $currentConnectionIsWriter;
    }
}
