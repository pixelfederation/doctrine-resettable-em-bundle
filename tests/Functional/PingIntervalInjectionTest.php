<?php
declare(strict_types=1);
/*
 * @author     mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional;

use Exception;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\OptimizedAliveKeeper;

final class PingIntervalInjectionTest extends TestCase
{
    /**
     * @throws Exception
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::bootTestKernel();
    }

    protected static function getTestCase(): string
    {
        return 'PingIntervalInjectionTest';
    }

    public function testPingIntervalInjectionFromConfiguration(): void
    {
        /* @var $handler OptimizedAliveKeeper */
        $handler = self::$container->get(OptimizedAliveKeeper::class);

        self::assertSame(10, $handler->getPingIntervalInSeconds());
    }
}
