<?php
declare(strict_types=1);
namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\Connection\AliveKeeper;

use Exception;
use PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper\AliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper\OptimizedAliveKeeper;
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
