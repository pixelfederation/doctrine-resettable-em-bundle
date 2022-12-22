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
        $keeper1 = $this->prophesize(PlatformAliveKeeper::class);
        $keeper1->keepAlive()->shouldBeCalledOnce();
        $keeper2 = $this->prophesize(PlatformAliveKeeper::class);
        $keeper2->keepAlive()->shouldBeCalledOnce();

        $handler = new ConnectionsHandler([$keeper1->reveal(), $keeper2->reveal()]);
        $handler->initialize();
    }
}
