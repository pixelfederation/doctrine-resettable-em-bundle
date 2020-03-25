<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\DBAL;

use Doctrine\Persistence\ConnectionRegistry;
use Doctrine\DBAL\Connection;
use Exception;
use PixelFederation\DoctrineResettableEmBundle\RequestCycle\InitializerInterface;

/**
 *
 */
final class ConnectionsHandler implements InitializerInterface
{
    /**
     * @var ConnectionRegistry
     */
    private $connectionRegistry;

    /**
     * @var int
     */
    private $lastPingAt;

    /**
     * @var int
     */
    private $pingIntervalInSeconds;

    /**
     * @const int
     */
    private const DEFAULT_PING_INTERVAL = 0;

    /**
     * @param ConnectionRegistry $connectionRegistry
     * @param int                $pingIntervalInSeconds
     *
     */
    public function __construct(
        ConnectionRegistry $connectionRegistry,
        int $pingIntervalInSeconds = self::DEFAULT_PING_INTERVAL
    ) {
        $this->connectionRegistry = $connectionRegistry;
        $this->lastPingAt = time();
        $this->pingIntervalInSeconds = $pingIntervalInSeconds;
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
    public function initialize(): void
    {
        if (!$this->isPingNeeded()) {
            return;
        }

        /** @var Connection $connection */
        foreach ($this->connectionRegistry->getConnections() as $connection) {
            if ($connection->ping()) {
                continue;
            }

            $connection->close();
            $connection->connect();
        }
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
