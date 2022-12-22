<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Connection;

interface PlatformAliveKeeper
{
    public function keepAlive(): void;
}
