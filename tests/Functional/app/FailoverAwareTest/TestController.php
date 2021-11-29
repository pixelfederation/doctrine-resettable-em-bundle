<?php

declare(strict_types=1);

/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\FailoverAwareTest;

use Doctrine\ORM\EntityManagerInterface;
use PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\HttpRequestLifecycleTest\Entity\TestEntity;
use Symfony\Component\HttpFoundation\Response;

final class TestController
{
    public function doNothingAction(): Response
    {
        return new Response();
    }
}
