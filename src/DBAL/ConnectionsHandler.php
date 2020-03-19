<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\DBAL;

use Doctrine\Common\Persistence\ConnectionRegistry;
use Doctrine\DBAL\Connection;
use Exception;
use PixelFederation\DoctrineResettableEmBundle\RequestCycle\InitializerInterface;
use Webmozart\Assert\Assert;

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
     * @var Connection[]|null
     */
    private $connections = null;

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

        foreach ($this->getConnections() as $connection) {
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

    /**
     * @return Connection[]
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @psalm-suppress MoreSpecificReturnType
     */
    private function getConnections(): array
    {
        if ($this->connections !== null) {
            return $this->connections;
        }

        $connections = $this->connectionRegistry->getConnections();
        Assert::allIsInstanceOf($connections, Connection::class);

        /**
         * @psalm-suppress LessSpecificReturnStatement
         *@psalm-suppress PropertyTypeCoercion
         */
        return $this->connections = $connections;
    }
}
