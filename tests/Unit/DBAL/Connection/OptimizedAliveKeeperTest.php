<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL\Connection;

use Exception;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\AliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\OptimizedAliveKeeper;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bridge\PhpUnit\ClockMock;

class OptimizedAliveKeeperTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @throws Exception
     * @group time-sensitive
     */
    public function testKeepAliveEachXSeconds(): void
    {
        ClockMock::register(OptimizedAliveKeeper::class);

        $decoratedAliveKeepr = $this->prophesize(AliveKeeper::class);
        $decoratedAliveKeepr->keepAlive()->shouldBeCalledOnce();

        $aliveKeeper = new OptimizedAliveKeeper($decoratedAliveKeepr->reveal(), 3);
        $aliveKeeper->keepAlive();
        sleep(4);
        $aliveKeeper->keepAlive();
    }
}
