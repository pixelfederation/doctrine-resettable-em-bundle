<?php
declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Exception;
use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\AliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\PassiveIgnoringAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Tests\Unit\Helper\ProxyConnectionMock;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ProxyManager\Proxy\VirtualProxyInterface;

class PassiveIgnoringAliveKeeperTest extends TestCase
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

        /** @var $decoratedAliveKeeper AliveKeeper|ObjectProphecy */
        $decoratedAliveKeeper = $this->prophesize(AliveKeeper::class);
        $decoratedAliveKeeper->keepAlive($connectionMock, $connectionName)
            ->shouldNotBeCalled();

        $aliveKeeper = new PassiveIgnoringAliveKeeper(
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

        /** @var $decoratedAliveKeeper AliveKeeper|ObjectProphecy */
        $decoratedAliveKeeper = $this->prophesize(AliveKeeper::class);
        $decoratedAliveKeeper->keepAlive($connectionMock, $connectionName)
            ->shouldNotBeCalled();

        $aliveKeeper = new PassiveIgnoringAliveKeeper(
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

        /** @var $decoratedAliveKeeper AliveKeeper|ObjectProphecy */
        $decoratedAliveKeeper = $this->prophesize(AliveKeeper::class);
        $decoratedAliveKeeper->keepAlive($connectionMock, $connectionName)
            ->shouldBeCalled();

        $aliveKeeper = new PassiveIgnoringAliveKeeper($decoratedAliveKeeper->reveal());
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
    }
}
