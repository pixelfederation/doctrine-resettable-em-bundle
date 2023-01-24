<?php
declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL\Connection;

use Doctrine\DBAL\Connection;
use Exception;
use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\DBALAliveKeeper;
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
        $connectionMock = $connectionProphecy->reveal();
        $connectionName = 'default';

        /** @var $decoratedAliveKeeper DBALAliveKeeper|ObjectProphecy */
        $decoratedAliveKeeper = $this->prophesize(DBALAliveKeeper::class);
        $decoratedAliveKeeper->keepAlive($connectionMock, $connectionName)
            ->shouldBeCalled();

        $aliveKeeper = new TransactionDiscardingDBALAliveKeeper($decoratedAliveKeeper->reveal(), $loggerProphecy->reveal());
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
    }

    public function testRollbackConnectionIfItIsInTransactionAndLogRollbackException(): void
    {
        $connectionName = 'default';
        $exceptionProphecy = $this->prophesize(Throwable::class)->reveal();

        /** @var $loggerProphecy LoggerInterface|ObjectProphecy */
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->error(
            sprintf('An error occurred while discarding active transaction in connection "%s".', $connectionName),
            ['exception' => $exceptionProphecy]
        )
            ->shouldBeCalled();
        $loggerProphecy->error(
            sprintf(
                'Connection "%s" needed to discard active transaction while running keep-alive routine.',
                $connectionName
            )
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

        $connectionMock = $connectionProphecy->reveal();

        /** @var $decoratedAliveKeeper DBALAliveKeeper|ObjectProphecy */
        $decoratedAliveKeeper = $this->prophesize(DBALAliveKeeper::class);
        $decoratedAliveKeeper->keepAlive($connectionMock, $connectionName)
            ->shouldBeCalled();

        $aliveKeeper = new TransactionDiscardingDBALAliveKeeper($decoratedAliveKeeper->reveal(), $loggerProphecy->reveal());
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
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

        $connectionMock = $connectionProphecy->reveal();
        $connectionName = 'default';

        /** @var $decoratedAliveKeeper DBALAliveKeeper|ObjectProphecy */
        $decoratedAliveKeeper = $this->prophesize(DBALAliveKeeper::class);
        $decoratedAliveKeeper->keepAlive($connectionMock, $connectionName)
            ->shouldBeCalled();

        $aliveKeeper = new TransactionDiscardingDBALAliveKeeper($decoratedAliveKeeper->reveal(), $loggerProphecy->reveal());
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
    }
}
