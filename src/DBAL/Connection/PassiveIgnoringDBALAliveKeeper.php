<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Override;
use Symfony\Component\VarExporter\LazyObjectInterface;

final class PassiveIgnoringDBALAliveKeeper implements DBALAliveKeeper
{
    public function __construct(
        private readonly DBALAliveKeeper $decorated,
    ) {
    }

    #[Override]
    public function keepAlive(Connection $connection, string $connectionName): void
    {
        if ($connection instanceof LazyObjectInterface && !$connection->isLazyObjectInitialized()) {
            return;
        }

        if (!$connection->isConnected()) {
            return;
        }

        $this->decorated->keepAlive($connection, $connectionName);
    }
}
