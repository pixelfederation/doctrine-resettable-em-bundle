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
     * @var Connection[]|null
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
     * @return Connection[]
     */
    private function getConnections(): array
    {
        if (!$this->connections) {
            $this->connections = array_map(
                static function (EntityManagerInterface $entityManager) {
                    return $entityManager->getConnection();
                },
                array_filter(
                    $this->doctrineRegistry->getManagers(),
                    static function (ObjectManager $objectManager) {
                        return $objectManager instanceof EntityManagerInterface;
                    }
                )
            );
        }

        return $this->connections;
    }
}
