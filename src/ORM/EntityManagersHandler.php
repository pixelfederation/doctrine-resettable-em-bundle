<?php

declare(strict_types=1);

/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\ORM;

use PixelFederation\DoctrineResettableEmBundle\RequestCycle\TerminatorInterface;

/**
 *
 */
class EntityManagersHandler implements TerminatorInterface
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
     * @return void
     */
    public function terminate(): void
    {
        foreach ($this->entityManagers as $entityManager) {
            $entityManager->clearOrResetIfNeeded();
        }
    }
}
