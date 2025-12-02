<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\HttpRequestLifecycleTest;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\HttpRequestLifecycleTest\ExcludedEntity\ExcludedTestEntity;
use PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\HttpRequestLifecycleTest\ExcludedEntity\ExcludedTestEntity2;
use Symfony\Component\HttpFoundation\Response;

final class ExcludedTestController
{
    private readonly EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $entityManager->getRepository(ExcludedTestEntity2::class);
    }

    public function doNothingAction(): Response
    {
        return new Response();
    }

    public function persistTestAction(): Response
    {
        $this->entityManager->persist(new ExcludedTestEntity());
        $this->entityManager->flush();

        return new Response();
    }

    public function persistErrorTestAction(): Response
    {
        try {
            $this->entityManager->persist(new ExcludedTestEntity2(10));
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
        }

        return new Response();
    }

    public function removeAllPersistedAction(): Response
    {
        $all = $this->repository->findAll();

        foreach ($all as $testEntity2) {
            $this->entityManager->remove($testEntity2);
        }

        $this->entityManager->flush();

        return new Response();
    }
}
