<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\HttpRequestLifecycleTest;

use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class EntityManagerChecker implements EventSubscriberInterface
{
    private int $numberOfChecks = 0;

    private bool $wasEmptyOnLastCheck = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'checkEntityManager',
        ];
    }

    public function checkEntityManager(): void
    {
        $this->numberOfChecks++;
        $uow = $this->entityManager->getUnitOfWork();
        $this->wasEmptyOnLastCheck = $uow->getIdentityMap() === [];
    }

    public function getNumberOfChecks(): int
    {
        return $this->numberOfChecks;
    }

    public function wasEmptyOnLastCheck(): bool
    {
        return $this->wasEmptyOnLastCheck;
    }
}
