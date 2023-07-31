<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionLost;
use Exception;

final class PingingDBALAliveKeeper implements DBALAliveKeeper
{
    /**
     * @throws Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    public function keepAlive(Connection $connection, string $connectionName): void
    {
        $query = $connection->getDatabasePlatform()->getDummySelectSQL();
        try {
            $connection->executeQuery($query);
        } catch (ConnectionLost) {
            $connection->close();
            /** @psalm-suppress InternalMethod */
            $connection->connect();
        }
    }
}
