<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper;

use Exception;

final class OptimizedAliveKeeper implements AliveKeeper
{
    private AliveKeeper $decorated;

    private int $pingIntervalInSeconds;

    private int $lastPingAt;

    /**
     * @const int
     */
    private const DEFAULT_PING_INTERVAL = 0;

    public function __construct(AliveKeeper $decorated, int $pingIntervalInSeconds = self::DEFAULT_PING_INTERVAL)
    {
        $this->decorated = $decorated;
        $this->pingIntervalInSeconds = $pingIntervalInSeconds;
        $this->lastPingAt = time();
    }

    public function getPingIntervalInSeconds(): int
    {
        return $this->pingIntervalInSeconds;
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
