<?php
declare(strict_types=1);

/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\RequestCycle;

/**
 *
 */
final class Initializers
{
    /**
     * @var InitializerInterface[]
     */
    private $initializers;

    /**
     * @param InitializerInterface[] $initializers
     */
    public function __construct($initializers)
    {
        $this->initializers = $initializers;
    }

    /**
     *
     */
    public function initialize(): void
    {
        foreach ($this->initializers as $initializer) {
            $initializer->initialize();
        }
    }
}
