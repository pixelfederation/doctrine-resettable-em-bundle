<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\Connection\AliveKeeper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionLost;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Exception;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\DBALAliveKeeper;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class SimpleAliveKeeperTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @throws Exception
     */
    public function testKeepAliveWithoutReconnect(): void
    {
        $query = 'SELECT 1';
        /** @var $platformProphecy AbstractPlatform|ObjectProphecy */
        $platformProphecy = $this->prophesize(AbstractPlatform::class);
        $platformProphecy->getDummySelectSQL()->willReturn($query)->shouldBeCalled();
        /** @var $connectionProphecy Connection|ObjectProphecy */
        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->getDatabasePlatform()
            ->willReturn($platformProphecy->reveal())
            ->shouldBeCalled();
        $connectionProphecy->executeQuery($query)->shouldBeCalled();
        $connectionProphecy->close()->shouldNotBeCalled();
        $connectionProphecy->connect()->shouldNotBeCalled();

        $aliveKeeper = new DBALAliveKeeper($connectionProphecy->reveal());
        $aliveKeeper->keepAlive();
    }

    /**
     * @throws Exception
     */
    public function testKeepAliveWithReconnectOnFailedPing(): void
    {
        $query = 'SELECT 1';
        /** @var $platformProphecy AbstractPlatform|ObjectProphecy */
        $platformProphecy = $this->prophesize(AbstractPlatform::class);
        $platformProphecy->getDummySelectSQL()->willReturn($query)->shouldBeCalled();
        /** @var $connectionProphecy Connection|ObjectProphecy */
        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->getDatabasePlatform()
            ->willReturn($platformProphecy->reveal())
            ->shouldBeCalled();
        $connectionProphecy->executeQuery($query)->willThrow(ConnectionLost::class);
        $connectionProphecy->close()->shouldBeCalled();
        $connectionProphecy->connect()->willReturn(true)->shouldBeCalled();

        $aliveKeeper = new DBALAliveKeeper($connectionProphecy->reveal());
        $aliveKeeper->keepAlive();
    }
}
