<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\DBAL;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
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
     * @param Registry $doctrineRegistry
     */
    public function __construct(Registry $doctrineRegistry)
    {
        $this->doctrineRegistry = $doctrineRegistry;
    }

    /**
     * @return void
     */
    public function initialize(): void
    {
        foreach ($this->getConnections() as $connection) {
            if ($connection->ping()) {
                continue;
            }

            $connection->close();
            $connection->connect();
        }
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
