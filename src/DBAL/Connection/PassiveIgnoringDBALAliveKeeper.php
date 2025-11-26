<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Override;
use ProxyManager\Proxy\VirtualProxyInterface;

final class PassiveIgnoringDBALAliveKeeper implements DBALAliveKeeper
{
    public function __construct(
        private readonly DBALAliveKeeper $decorated,
    ) {
    }

    #[Override]
    public function keepAlive(Connection $connection, string $connectionName): void
    {
        if ($connection instanceof VirtualProxyInterface && !$connection->isProxyInitialized()) {
            return;
        }

        if (!$connection->isConnected()) {
            return;
        }

        $this->decorated->keepAlive($connection, $connectionName);
    }
}
