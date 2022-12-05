<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper;

use Exception;

final class AggregatedAliveKeeper implements AliveKeeper
{
    /**
     * @var array<AliveKeeper>
     */
    private array $aliveKeepers;

    /**
     * @param array<AliveKeeper> $aliveKeepers
     */
    public function __construct(array $aliveKeepers)
    {
        $this->aliveKeepers = $aliveKeepers;
    }

    /**
     * @throws Exception
     */
    public function keepAlive(): void
    {
        foreach ($this->aliveKeepers as $aliveKeeper) {
            $aliveKeeper->keepAlive();
        }
    }
}
