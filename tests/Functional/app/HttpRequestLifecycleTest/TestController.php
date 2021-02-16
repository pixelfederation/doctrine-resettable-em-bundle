<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\HttpRequestLifecycleTest;

use Doctrine\ORM\EntityManagerInterface;
use PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\HttpRequestLifecycleTest\Entity\TestEntity;
use Symfony\Component\HttpFoundation\Response;

final class TestController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function doNothingAction(): Response
    {
        return new Response();
    }

    public function persistTestAction(): Response
    {
        $this->entityManager->persist(new TestEntity());
        $this->entityManager->flush();

        return new Response();
    }
}
