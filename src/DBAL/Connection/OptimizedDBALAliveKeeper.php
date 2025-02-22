<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Exception;

final class OptimizedDBALAliveKeeper implements DBALAliveKeeper
{
    /**
     * @const int
     */
    private const DEFAULT_PING_INTERVAL = 0;

    private int $lastPingAt;

    public function __construct(
        private readonly DBALAliveKeeper $decorated,
        private readonly int $pingIntervalInSeconds = self::DEFAULT_PING_INTERVAL,
    ) {
        $this->lastPingAt = 0;
    }

    /**
     * @throws Exception
     */
    public function keepAlive(Connection $connection, string $connectionName): void
    {
        if (!$this->isPingNeeded()) {
            return;
        }

        $this->decorated->keepAlive($connection, $connectionName);
    }

    /**
     * @throws Exception
     */
    private function isPingNeeded(): bool
    {
        $lastPingAt = $this->lastPingAt;
        $now = time();
        $this->lastPingAt = $now;

        return $now - $lastPingAt >= $this->pingIntervalInSeconds;
    }
}
