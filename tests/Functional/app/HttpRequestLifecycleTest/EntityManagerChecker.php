<?php

declare(strict_types=1);

/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\HttpRequestLifecycleTest;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class EntityManagerChecker implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;

    private int $numberOfChecks = 0;

    private bool $wasEmptyOnLastCheck = false;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function checkEntityManager(): void
    {
        $this->numberOfChecks++;
        $uow = $this->entityManager->getUnitOfWork();
        $this->wasEmptyOnLastCheck = empty($uow->getIdentityMap());
    }

    public function getNumberOfChecks(): int
    {
        return $this->numberOfChecks;
    }

    public function wasEmptyOnLastCheck(): bool
    {
        return $this->wasEmptyOnLastCheck;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'checkEntityManager',
        ];
    }
}
