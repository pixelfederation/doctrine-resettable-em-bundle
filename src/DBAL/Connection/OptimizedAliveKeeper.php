<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection;

use Exception;

/**
 */
final class OptimizedAliveKeeper implements AliveKeeper
{
    /**
     * @var AliveKeeper
     */
    private $decorated;

    /**
     * @var int
     */
    private $pingIntervalInSeconds;

    /**
     * @var int
     */
    private $lastPingAt;

    /**
     * @const int
     */
    private const DEFAULT_PING_INTERVAL = 0;

    /**
     * @param AliveKeeper $decorated
     * @param int         $pingIntervalInSeconds
     */
    public function __construct(AliveKeeper $decorated, int $pingIntervalInSeconds = self::DEFAULT_PING_INTERVAL)
    {
        $this->decorated = $decorated;
        $this->pingIntervalInSeconds = $pingIntervalInSeconds;
        $this->lastPingAt = time();
    }

    /**
     * @return int
     */
    public function getPingIntervalInSeconds(): int
    {
        return $this->pingIntervalInSeconds;
    }

    /**
     * @return void
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
     * @return bool
     * @throws Exception
     */
    private function isPingNeeded(): bool
    {
        $lastPingAt = $this->lastPingAt;
        $now = $this->lastPingAt = time();

        return $now - $lastPingAt >= $this->pingIntervalInSeconds;
    }
}
