<?php

declare(strict_types=1);

/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper;

interface AliveKeeper
{
    public function keepAlive(): void;
}
