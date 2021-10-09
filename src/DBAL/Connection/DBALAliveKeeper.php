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
        $query = $this->connection->getDatabasePlatform()->getDummySelectSQL();
        try {
            // in addition to the error, PHP 7.3 will generate a warning that needs to be
            // suppressed in order to not let PHPUnit handle it before the actual error
            @$this->connection->executeQuery($query);
        } catch (ConnectionLost $e) {
            $this->connection->close();
            $this->connection->connect();
        }
    }
}
