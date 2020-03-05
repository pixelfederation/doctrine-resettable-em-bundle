<?php
declare(strict_types=1);
/*
 * @author     mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional;

use Exception;
use PixelFederation\DoctrineResettableEmBundle\DBAL\ConnectionsHandler;

/**
 */
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

    /**
     * @return string
     */
    protected static function getTestCase(): string
    {
        return 'PingIntervalInjectionTest';
    }

    /**
     *
     */
    public function testPingIntervalInjectionFromConfiguration(): void
    {
        /* @var $handler ConnectionsHandler */
        $handler = self::$container->get(ConnectionsHandler::class);

        self::assertSame(10, $handler->getPingIntervalInSeconds());
    }
}
