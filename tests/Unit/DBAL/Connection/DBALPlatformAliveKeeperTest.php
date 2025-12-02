<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL\Connection;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\DBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\DBALPlatformAliveKeeper;

final class DBALPlatformAliveKeeperTest extends TestCase
{
    public function testKeepAlive(): void
    {
        $cName1 = 'default';
        $cMock1 = $this->createMock(Connection::class);
        $cName2 = 'other';
        $cMock2 = $this->createMock(Connection::class);

        $keeper1 = $this->createMock(DBALAliveKeeper::class);
        $keeper1->expects($this->once())->method('keepAlive')->with($cMock1, $cName1);
        $keeper2 = $this->createMock(DBALAliveKeeper::class);
        $keeper2->expects($this->once())->method('keepAlive')->with($cMock2, $cName2);

        $platformKeeper = new DBALPlatformAliveKeeper(
            [
                $cName1 => $cMock1,
                $cName2 => $cMock2,
            ],
            [
                $cName1 => $keeper1,
                $cName2 => $keeper2,
            ],
        );
        $platformKeeper->keepAlive();
    }
}
