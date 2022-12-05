<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\RequestCycle;

interface Initializer
{
    public function initialize(): void;
}
