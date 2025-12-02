<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\FailoverAwareTest;

use Symfony\Component\HttpFoundation\Response;

final class TestController
{
    public function doNothingAction(): Response
    {
        return new Response();
    }
}
