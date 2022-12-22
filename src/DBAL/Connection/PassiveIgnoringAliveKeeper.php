<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Exception;
use ProxyManager\Proxy\VirtualProxyInterface;

final class PassiveIgnoringAliveKeeper implements AliveKeeper
{
    private AliveKeeper $decorated;

    public function __construct(AliveKeeper $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * @throws Exception
     */
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
