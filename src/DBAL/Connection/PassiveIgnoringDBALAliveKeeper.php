<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Exception;
use PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper\AliveKeeper;
use ProxyManager\Proxy\VirtualProxyInterface;

final class PassiveIgnoringDBALAliveKeeper implements AliveKeeper
{
    private AliveKeeper $decorated;

    private Connection $connection;

    public function __construct(AliveKeeper $decorated, Connection $connection)
    {
        $this->decorated = $decorated;
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

        if (!$this->connection->isConnected()) {
            return;
        }

        $this->decorated->keepAlive();
    }
}
