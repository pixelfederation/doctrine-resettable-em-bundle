<?php

declare(strict_types=1);

/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\RequestCycle;

final class Initializers
{
    /**
     * @var iterable<Initializer>
     */
    private iterable $initializers;

    /**
     * @param iterable<Initializer> $initializers
     */
    public function __construct(iterable $initializers)
    {
        $this->initializers = $initializers;
    }

    public function initialize(): void
    {
        foreach ($this->initializers as $initializer) {
            $initializer->initialize();
        }
    }
}
