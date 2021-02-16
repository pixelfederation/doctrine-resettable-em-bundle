<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\RequestCycle;

final class Terminators
{
    /**
     * @var TerminatorInterface[]
     */
    private iterable $terminators;

    /**
     * @param TerminatorInterface[] $terminators
     */
    public function __construct(iterable $terminators)
    {
        $this->terminators = $terminators;
    }

    public function terminate(): void
    {
        foreach ($this->terminators as $terminator) {
            $terminator->terminate();
        }
    }
}
