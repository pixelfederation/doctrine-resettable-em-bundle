<?php
declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Exception;
use PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper\AliveKeeper;
use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\TransactionDiscardingDBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Tests\Unit\Helper\ProxyConnectionMock;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ProxyManager\Proxy\VirtualProxyInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class TransactionDiscardingDBALAliveKeeperTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @throws Exception
     */
    public function testRollbackConnectionIfItIsInTransaction(): void
    {
        /** @var $loggerProphecy LoggerInterface|ObjectProphecy */
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->error('Connection "default" needed to discard active transaction while running keep-alive routine.')
            ->shouldBeCalled();
        ;
        /** @var $connectionProphecy VirtualProxyInterface|Connection|ObjectProphecy */
        $connectionProphecy = $this->prophesize(ProxyConnectionMock::class);
        $connectionProphecy->isTransactionActive()
            ->willReturn(true)
            ->shouldBeCalled();
        $connectionProphecy->rollBack()
            ->shouldBeCalled();

        /** @var $decoratedAliveKeeper AliveKeeper|ObjectProphecy */
        $decoratedAliveKeeper = $this->prophesize(AliveKeeper::class);
        $decoratedAliveKeeper->keepAlive()
            ->shouldBeCalled();

        $aliveKeeper = new TransactionDiscardingDBALAliveKeeper(
            $decoratedAliveKeeper->reveal(),
            $connectionProphecy->reveal(),
            'default',
            $loggerProphecy->reveal()
        );
        $aliveKeeper->keepAlive();
    }

    public function testRollbackConnectionIfItIsInTransactionAndLogRollbackException(): void
    {
        $exceptionProphecy = $this->prophesize(Throwable::class)->reveal();

        /** @var $loggerProphecy LoggerInterface|ObjectProphecy */
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->error(
            'An error occurred while discarding active transaction in connection "default".',
            ['exception' => $exceptionProphecy]
        )
            ->shouldBeCalled();
        $loggerProphecy->error(
            'Connection "default" needed to discard active transaction while running keep-alive routine.'
        )
            ->shouldBeCalled();

        /** @var $connectionProphecy VirtualProxyInterface|Connection|ObjectProphecy */
        $connectionProphecy = $this->prophesize(ProxyConnectionMock::class);
        $connectionProphecy->isTransactionActive()
            ->willReturn(true)
            ->shouldBeCalled();
        $connectionProphecy->rollBack()
            ->willThrow($exceptionProphecy)
            ->shouldBeCalled();

        /** @var $decoratedAliveKeeper AliveKeeper|ObjectProphecy */
        $decoratedAliveKeeper = $this->prophesize(AliveKeeper::class);
        $decoratedAliveKeeper->keepAlive()
            ->shouldBeCalled();

        $aliveKeeper = new TransactionDiscardingDBALAliveKeeper(
            $decoratedAliveKeeper->reveal(),
            $connectionProphecy->reveal(),
            'default',
            $loggerProphecy->reveal()
        );
        $aliveKeeper->keepAlive();
    }

    public function testDoNotRollbackConnectionIfItIsNotInTransaction(): void
    {
        /** @var $loggerProphecy LoggerInterface|ObjectProphecy */
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->error(Argument::any())
            ->shouldNotBeCalled();
        ;
        /** @var $connectionProphecy VirtualProxyInterface|Connection|ObjectProphecy */
        $connectionProphecy = $this->prophesize(ProxyConnectionMock::class);
        $connectionProphecy->isTransactionActive()
            ->willReturn(false)
            ->shouldBeCalled();
        $connectionProphecy->rollBack()
            ->shouldNotBeCalled();

        /** @var $decoratedAliveKeeper AliveKeeper|ObjectProphecy */
        $decoratedAliveKeeper = $this->prophesize(AliveKeeper::class);
        $decoratedAliveKeeper->keepAlive()
            ->shouldBeCalled();

        $aliveKeeper = new TransactionDiscardingDBALAliveKeeper(
            $decoratedAliveKeeper->reveal(),
            $connectionProphecy->reveal(),
            'default',
            $loggerProphecy->reveal()
        );
        $aliveKeeper->keepAlive();
    }
}
