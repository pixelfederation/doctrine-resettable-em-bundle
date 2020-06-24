<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection;

use Exception;

/**
 */
final class AggregatedAliveKeeper implements AliveKeeper
{
    /**
     * @var AliveKeeper[]
     */
    private $aliveKeepers;

    /**
     * @param AliveKeeper[] $aliveKeepers
     */
    public function __construct(array $aliveKeepers)
    {
        $this->aliveKeepers = $aliveKeepers;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function keepAlive(): void
    {
        foreach ($this->aliveKeepers as $aliveKeeper) {
            $aliveKeeper->keepAlive();
        }
    }
}
