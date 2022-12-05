<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Connection;

use Exception;
use PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper\AliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\RequestCycle\Initializer;

final class ConnectionsHandler implements Initializer
{
    private AliveKeeper $aliveKeeper;

    public function __construct(AliveKeeper $aliveKeeper)
    {
        $this->aliveKeeper = $aliveKeeper;
    }

    /**
     * @throws Exception
     */
    public function initialize(): void
    {
        $this->aliveKeeper->keepAlive();
    }
}
