<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionLost;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Exception;
use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\PingingAliveKeeper;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class PingingAliveKeeperTest extends TestCase
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

        $aliveKeeper = new PingingAliveKeeper();
        $aliveKeeper->keepAlive($connectionProphecy->reveal(), 'default');
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

        $aliveKeeper = new PingingAliveKeeper();
        $aliveKeeper->keepAlive($connectionProphecy->reveal(), 'default');
    }
}
