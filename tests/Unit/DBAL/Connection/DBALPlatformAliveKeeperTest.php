<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL\Connection;

use Doctrine\DBAL\Connection;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\DBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\DBALPlatformAliveKeeper;
use PHPUnit\Framework\TestCase;

class DBALPlatformAliveKeeperTest extends TestCase
{
    public function testKeepAlive()
    {
        $cName1 = 'default';
        $cMock1 = $this->prophesize(Connection::class);
        $cMock1 = $cMock1->reveal();
        $cName2 = 'other';
        $cMock2 = $this->prophesize(Connection::class);
        $cMock2 = $cMock2->reveal();

        $keeper1 = $this->prophesize(DBALAliveKeeper::class);
        $keeper1->keepAlive($cMock1, $cName1)->shouldBeCalledOnce();
        $keeper2 = $this->prophesize(DBALAliveKeeper::class);
        $keeper2->keepAlive($cMock2, $cName2)->shouldBeCalledOnce();

        $platformKeeper = new DBALPlatformAliveKeeper(
            [
                $cName1 => $cMock1,
                $cName2 => $cMock2,
            ],
            [
                $cName1 => $keeper1->reveal(),
                $cName2 => $keeper2->reveal(),
            ]
        );
        $platformKeeper->keepAlive();
    }
}
