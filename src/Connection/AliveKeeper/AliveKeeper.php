<?php

declare(strict_types=1);

/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper;

interface AliveKeeper
{
    public function keepAlive(): void;
}
