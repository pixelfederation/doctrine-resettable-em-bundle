<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionLost;
use Override;

final class PingingDBALAliveKeeper implements DBALAliveKeeper
{
    #[Override]
    public function keepAlive(Connection $connection, string $connectionName): void
    {
        $query = $connection->getDatabasePlatform()->getDummySelectSQL();

        try {
            $connection->executeQuery($query);
        } catch (ConnectionLost) {
            $connection->close();
            $connection->getNativeConnection();
        }
    }
}
