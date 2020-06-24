<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Exception;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\SimpleAliveKeeper;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

class SimpleAliveKeeperTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testKeepAliveWithoutReconnect(): void
    {
        /** @var $connectionProphecy Connection|ObjectProphecy */
        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->ping()->willReturn(true)->shouldBeCalled();
        $connectionProphecy->close()->shouldNotBeCalled();
        $connectionProphecy->connect()->shouldNotBeCalled();

        $aliveKeeper = new SimpleAliveKeeper($connectionProphecy->reveal());
        $aliveKeeper->keepAlive();
    }

    /**
     * @throws Exception
     */
    public function testKeepAliveWithReconnectOnFailedPing(): void
    {
        /** @var $connectionProphecy Connection|ObjectProphecy */
        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->ping()->willReturn(false)->shouldBeCalled();
        $connectionProphecy->close()->shouldBeCalled();
        $connectionProphecy->connect()->willReturn(true)->shouldBeCalled();

        $aliveKeeper = new SimpleAliveKeeper($connectionProphecy->reveal());
        $aliveKeeper->keepAlive();
    }
}
