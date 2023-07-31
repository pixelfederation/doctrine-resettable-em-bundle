<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Connection;

use Exception;
use PixelFederation\DoctrineResettableEmBundle\RequestCycle\Initializer;

final class ConnectionsHandler implements Initializer
{
    /**
     * @param array<PlatformAliveKeeper> $aliveKeepers
     */
    public function __construct(
        private readonly array $aliveKeepers,
    ) {
    }

    /**
     * @throws Exception
     */
    public function initialize(): void
    {
        foreach ($this->aliveKeepers as $aliveKeeper) {
            $aliveKeeper->keepAlive();
        }
    }
}
