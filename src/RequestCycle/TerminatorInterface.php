<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\RequestCycle;

/**
 *
 */
interface TerminatorInterface
{
    /**
     *
     */
    public function terminate(): void;
}
