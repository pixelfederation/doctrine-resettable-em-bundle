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

final class SimpleAliveKeeper implements AliveKeeper
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
        /** @psalm-suppress DeprecatedMethod */
        if ($this->connection->ping()) {
            return;
        }

        $this->connection->close();
        $this->connection->connect();
    }
}
