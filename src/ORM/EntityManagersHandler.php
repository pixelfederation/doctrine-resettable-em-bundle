<?php

declare(strict_types=1);

/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\ORM;

use PixelFederation\DoctrineResettableEmBundle\RequestCycle\InitializerInterface;
use PixelFederation\DoctrineResettableEmBundle\RequestCycle\TerminatorInterface;

/**
 *
 */
class EntityManagersHandler implements InitializerInterface, TerminatorInterface
{
    /**
     * @var ResettableEntityManager[]
     */
    private $entityManagers;

    /**
     * @param ResettableEntityManager[] $entityManagers
     */
    public function __construct(array $entityManagers)
    {
        $this->entityManagers = $entityManagers;
    }

    /**
     * this reset should help on request start if the app should be in an inconsistent state after an exception
     * or an error, which might prevent the second reset (in request termination stage) to activate
     *
     * @return void
     */
    public function initialize(): void
    {
        $this->resetEntityManagers();
    }

    /**
     * this reset should help to free memory after each request
     *
     * @return void
     */
    public function terminate(): void
    {
        $this->resetEntityManagers();
    }

    /**
     *
     */
    private function resetEntityManagers(): void
    {
        foreach ($this->entityManagers as $entityManager) {
            $entityManager->clearOrResetIfNeeded();
        }
    }
}
