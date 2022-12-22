<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use PixelFederation\DoctrineResettableEmBundle\Connection\PlatformAliveKeeper as GenericPlatformAliveKeeper;

final class PlatformAliveKeeper implements GenericPlatformAliveKeeper
{
    private ManagerRegistry $registry;

    /**
     * @var array<string, AliveKeeper>
     */
    private array $aliveKeepers;

    /**
     * @param array<string, AliveKeeper> $aliveKeepers
     */
    public function __construct(ManagerRegistry $registry, array $aliveKeepers)
    {
        $this->registry = $registry;
        $this->aliveKeepers = $aliveKeepers;
    }

    public function keepAlive(): void
    {
        foreach ($this->aliveKeepers as $connectionName => $aliveKeeper) {
            /** @var Connection $connection */
            $connection = $this->registry->getConnection($connectionName);
            $aliveKeeper->keepAlive($connection, $connectionName);
        }
    }
}
