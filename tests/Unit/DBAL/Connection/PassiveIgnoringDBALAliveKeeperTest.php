<?php
declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Exception;
use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\DBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\PassiveIgnoringDBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Tests\Unit\Helper\ProxyConnectionMock;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ProxyManager\Proxy\VirtualProxyInterface;

class PassiveIgnoringDBALAliveKeeperTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @throws Exception
     */
    public function testKeepAliveWithoutInitialisedConnectionProxyDoesNotDoAnything(): void
    {
        /** @var $connectionProphecy VirtualProxyInterface|Connection|ObjectProphecy */
        $connectionProphecy = $this->prophesize(ProxyConnectionMock::class);
        $connectionProphecy->isProxyInitialized()
            ->willReturn(false)
            ->shouldBeCalled();
        $connectionProphecy->getDatabasePlatform()
            ->shouldNotBeCalled();
        $connectionMock = $connectionProphecy->reveal();
        $connectionName = 'default';

        /** @var $decoratedAliveKeeper DBALAliveKeeper|ObjectProphecy */
        $decoratedAliveKeeper = $this->prophesize(DBALAliveKeeper::class);
        $decoratedAliveKeeper->keepAlive($connectionMock, $connectionName)
            ->shouldNotBeCalled();

        $aliveKeeper = new PassiveIgnoringDBALAliveKeeper(
            $decoratedAliveKeeper->reveal());
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
    }

    public function testKeepAliveWithoutInitialisedConnectionDoesNotDoAnything(): void
    {
        /** @var $connectionProphecy Connection|ObjectProphecy */
        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->isConnected()
            ->willReturn(false)
            ->shouldBeCalled();
        $connectionProphecy->getDatabasePlatform()
            ->shouldNotBeCalled();
        $connectionMock = $connectionProphecy->reveal();
        $connectionName = 'default';

        /** @var $decoratedAliveKeeper DBALAliveKeeper|ObjectProphecy */
        $decoratedAliveKeeper = $this->prophesize(DBALAliveKeeper::class);
        $decoratedAliveKeeper->keepAlive($connectionMock, $connectionName)
            ->shouldNotBeCalled();

        $aliveKeeper = new PassiveIgnoringDBALAliveKeeper(
            $decoratedAliveKeeper->reveal());
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
    }

    public function testKeepAliveWithInitialisedConnectionDelegatesControl(): void
    {
        /** @var $connectionProphecy VirtualProxyInterface|Connection|ObjectProphecy */
        $connectionProphecy = $this->prophesize(ProxyConnectionMock::class);
        $connectionProphecy->isProxyInitialized()
            ->willReturn(true)
            ->shouldBeCalled();
        $connectionProphecy->isConnected()
            ->willReturn(true)
            ->shouldBeCalled();
        $connectionProphecy->getDatabasePlatform()
            ->shouldNotBeCalled();
        $connectionMock = $connectionProphecy->reveal();
        $connectionName = 'default';

        /** @var $decoratedAliveKeeper DBALAliveKeeper|ObjectProphecy */
        $decoratedAliveKeeper = $this->prophesize(DBALAliveKeeper::class);
        $decoratedAliveKeeper->keepAlive($connectionMock, $connectionName)
            ->shouldBeCalled();

        $aliveKeeper = new PassiveIgnoringDBALAliveKeeper($decoratedAliveKeeper->reveal());
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
    }
}
