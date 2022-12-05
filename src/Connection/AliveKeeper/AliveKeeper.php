<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper;

interface AliveKeeper
{
    public function keepAlive(): void;
}
