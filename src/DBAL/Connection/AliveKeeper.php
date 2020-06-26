<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection;

/**
 */
interface AliveKeeper
{
    /**
     * @return void
     */
    public function keepAlive(): void;
}
