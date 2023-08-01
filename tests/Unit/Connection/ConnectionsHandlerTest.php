<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\Connection;

use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\Connection\ConnectionsHandler;
use PixelFederation\DoctrineResettableEmBundle\Connection\PlatformAliveKeeper;

class ConnectionsHandlerTest extends TestCase
{
    public function testKeepAliveAllConnections(): void
    {
        $keeper1 = $this->createMock(PlatformAliveKeeper::class);
        $keeper1->expects(self::once())->method('keepAlive');
        $keeper2 = $this->createMock(PlatformAliveKeeper::class);
        $keeper2->expects(self::once())->method('keepAlive');

        $handler = new ConnectionsHandler([$keeper1, $keeper2]);
        $handler->initialize();
    }
}
