<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper;

use Exception;

final class OptimizedAliveKeeper implements AliveKeeper
{
    /**
     * @const int
     */
    private const DEFAULT_PING_INTERVAL = 0;

    private AliveKeeper $decorated;

    private int $pingIntervalInSeconds;

    private int $lastPingAt;

    public function __construct(AliveKeeper $decorated, int $pingIntervalInSeconds = self::DEFAULT_PING_INTERVAL)
    {
        $this->decorated = $decorated;
        $this->pingIntervalInSeconds = $pingIntervalInSeconds;
        $this->lastPingAt = 0;
    }

    /**
     * @throws Exception
     */
    public function keepAlive(): void
    {
        if (!$this->isPingNeeded()) {
            return;
        }

        $this->decorated->keepAlive();
    }

    /**
     * @throws Exception
     */
    private function isPingNeeded(): bool
    {
        $lastPingAt = $this->lastPingAt;
        $now = $this->lastPingAt = time();

        return $now - $lastPingAt >= $this->pingIntervalInSeconds;
    }
}
