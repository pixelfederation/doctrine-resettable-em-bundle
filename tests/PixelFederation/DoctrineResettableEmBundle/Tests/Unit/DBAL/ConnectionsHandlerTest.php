<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PixelFederation\DoctrineResettableEmBundle\DBAL\ConnectionsHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bridge\PhpUnit\ClockMock;

class ConnectionsHandlerTest extends TestCase
{

    /**
     * @var ConnectionsHandler
     */
    private $connectionsHandler;

    /**
     * @var Registry|ObjectProphecy
     */
    private $doctrineRegistryProphecy;

    /**
     * @var EntityManagerInterface|ObjectProphecy
     */
    private $entityManagerProphecy;

    /**
     * @var Connection|ObjectProphecy
     */
    private $connectionProphecy;

    /**
     *
     * @param int $pingInterval
     *
     * @throws Exception
     */
    protected function setUpWithPingInterval(int $pingInterval = 0): void
    {
        $this->entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $this->doctrineRegistryProphecy = $this->prophesize(Registry::class);
        $this->connectionProphecy = $this->prophesize(Connection::class);

        /** @var Registry $doctrineRegistryMock */
        $doctrineRegistryMock = $this->doctrineRegistryProphecy->reveal();

        $this->setUpRegistryEnityManagers();
        $this->setUpEntityManagerConnection();
        $this->connectionsHandler = new ConnectionsHandler($doctrineRegistryMock, $pingInterval);
    }

    /**
     *
     * @throws Exception
     */
    public function testHandleNoReconnectOnAppInitialize(): void
    {
        $this->setUpWithPingInterval();
        $this->connectionProphecy->ping()->willReturn(true)->shouldBeCalled();
        $this->connectionsHandler->initialize();
    }

    /**
     *
     * @throws Exception
     */
    public function testHandleWithReconnectOnAppInitialize(): void
    {
        $this->setUpWithPingInterval();
        $this->connectionProphecy->ping()->willReturn(false)->shouldBeCalled();
        $this->connectionProphecy->close()->shouldBeCalled();
        $this->connectionProphecy->connect()->willReturn(true)->shouldBeCalled();

        $this->connectionsHandler->initialize();
    }

    /**
     *
     */
    private function setUpRegistryEnityManagers(): void
    {
        $this->doctrineRegistryProphecy->getManagers()->willReturn([$this->entityManagerProphecy->reveal()]);
    }

    /**
     *
     */
    private function setUpEntityManagerConnection(): void
    {
        $this->entityManagerProphecy->getConnection()->willReturn($this->connectionProphecy->reveal());
    }

    /**
     * @group time-sensitive
     * @throws Exception
     */
    public function testHandleReconnectEachXSeconds(): void
    {
        ClockMock::register(ConnectionsHandler::class);
        $this->setUpWithPingInterval(1);
        $this->connectionProphecy->ping()->willReturn(true)->shouldBeCalledOnce();
        $this->connectionsHandler->initialize();
        sleep(2);
        $this->connectionsHandler->initialize();
    }
}
