<?php

declare(strict_types=1);

/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionLost;
use Exception;
use PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper\AliveKeeper;
use ProxyManager\Proxy\VirtualProxyInterface;

final class DBALAliveKeeper implements AliveKeeper
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws Exception
     */
    public function keepAlive(): void
    {
        if ($this->connection instanceof VirtualProxyInterface && !$this->connection->isProxyInitialized()) {
            return;
        }

        $query = $this->connection->getDatabasePlatform()->getDummySelectSQL();
        try {
            $this->connection->executeQuery($query);
        } catch (ConnectionLost $e) {
            $this->connection->close();
            /** @psalm-suppress InternalMethod */
            $this->connection->connect();
        }
    }
}
