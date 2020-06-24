<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\DBAL;

use Exception;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\AliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\RequestCycle\InitializerInterface;

/**
 *
 */
final class ConnectionsHandler implements InitializerInterface
{
    /**
     * @var AliveKeeper
     */
    private $aliveKeeper;

    /**
     * @param AliveKeeper $aliveKeeper
     */
    public function __construct(AliveKeeper $aliveKeeper)
    {
        $this->aliveKeeper = $aliveKeeper;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function initialize(): void
    {
        $this->aliveKeeper->keepAlive();
    }
}
