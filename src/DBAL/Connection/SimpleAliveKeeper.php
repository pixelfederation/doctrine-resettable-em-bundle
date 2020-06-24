<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Exception;

/**
 */
final class SimpleAliveKeeper implements AliveKeeper
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function keepAlive(): void
    {
        if ($this->connection->ping()) {
            return;
        }

        $this->connection->close();
        $this->connection->connect();
    }
}
