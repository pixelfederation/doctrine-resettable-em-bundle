<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection;

use Doctrine\DBAL\Connection;
use PixelFederation\DoctrineResettableEmBundle\Connection\PlatformAliveKeeper as GenericPlatformAliveKeeper;
use RuntimeException;

final class DBALPlatformAliveKeeper implements GenericPlatformAliveKeeper
{
    /**
     * @var array<string, Connection>
     */
    private array $connections;

    /**
     * @var array<string, DBALAliveKeeper>
     */
    private array $aliveKeepers;

    /**
     * @param array<string, Connection> $connections
     * @param array<string, DBALAliveKeeper> $aliveKeepers
     */
    public function __construct(array $connections, array $aliveKeepers)
    {
        $this->connections = $connections;
        $this->aliveKeepers = $aliveKeepers;
    }

    public function keepAlive(): void
    {
        foreach ($this->aliveKeepers as $connectionName => $aliveKeeper) {
            if (!isset($this->connections[$connectionName])) {
                throw new RuntimeException(
                    sprintf('Connection "%s" is missing.', $connectionName)
                );
            }

            $connection = $this->connections[$connectionName];
            $aliveKeeper->keepAlive($connection, $connectionName);
        }
    }

    public function addAliveKeeper(string $connectionName, Connection $connection, DBALAliveKeeper $aliveKeeper): void
    {
        $this->connections[$connectionName] = $connection;
        $this->aliveKeepers[$connectionName] = $aliveKeeper;
    }

    public function removeAliveKeeper(string $connectionName): void
    {
        unset($this->connections[$connectionName]);
        unset($this->aliveKeepers[$connectionName]);
    }
}
