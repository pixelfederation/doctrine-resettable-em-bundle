<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\DBAL;

use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PixelFederation\DoctrineResettableEmBundle\RequestCycle\InitializerInterface;

/**
 *
 */
final class ConnectionsHandler implements InitializerInterface
{
    /**
     * @var Registry
     */
    private $doctrineRegistry;

    /**
     * @var array<string,Connection>|null
     */
    private $connections = null;

    /**
     * @var DateTimeImmutable
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
     * @param Registry $doctrineRegistry
     * @param int      $pingIntervalInSeconds
     *
     * @throws Exception
     */
    public function __construct(Registry $doctrineRegistry, int $pingIntervalInSeconds = self::DEFAULT_PING_INTERVAL)
    {
        $this->doctrineRegistry = $doctrineRegistry;
        $this->lastPingAt = new DateTimeImmutable();
        $this->pingIntervalInSeconds = $pingIntervalInSeconds;
    }

    /**
     * @param int $pingIntervalInSeconds
     */
    public function setPingIntervalInSeconds(int $pingIntervalInSeconds): void
    {
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
        $now = $this->lastPingAt = new DateTimeImmutable();

        return $now->getTimestamp() - $lastPingAt->getTimestamp() >= $this->pingIntervalInSeconds;
    }

    /**
     * @return array<string,Connection>
     */
    private function getConnections(): array
    {
        if ($this->connections !== null) {
            return $this->connections;
        }

        return $this->connections =
            array_reduce(
                array_map(
                    static function (EntityManagerInterface $entityManager): Connection {
                        return $entityManager->getConnection();
                    },
                    array_filter(
                        $this->doctrineRegistry->getManagers(),
                        static function (ObjectManager $objectManager): bool {
                            return $objectManager instanceof EntityManagerInterface;
                        }
                    )
                ),
                /**
                 * @param array      $connections
                 * @param Connection $connection
                 *
                 * @return array<string,Connection>
                 * @psalm-suppress MixedReturnTypeCoercion
                 */
                static function (array $connections, Connection $connection): array {
                    $hash = spl_object_hash($connection);

                    if (!isset($connections[$hash])) {
                        $connections[$hash] = $connection;
                    }

                    /** @psalm-suppress MixedReturnTypeCoercion */
                    return $connections;
                },
                []
            );
    }
}
