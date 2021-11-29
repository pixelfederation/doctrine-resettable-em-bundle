<?php

declare(strict_types=1);

/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL\Connection\FailoverAware;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Result;
use Exception;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\FailoverAware\ConnectionType;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\FailoverAware\FailoverAwareAliveKeeper;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class FailoverAwareAliveKeeperTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @throws Exception
     */
    public function testKeepAliveWriterWithoutReconnect(): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $statementProphecy = $this->prophesize(Result::class);
        $statementProphecy->fetchOne()->willReturn('0')->shouldBeCalled();

        /** @var $connectionProphecy Connection|ObjectProphecy */
        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->executeQuery(Argument::any())->willReturn($statementProphecy->reveal())->shouldBeCalled();
        $connectionProphecy->close()->shouldNotBeCalled();
        $connectionProphecy->connect()->shouldNotBeCalled();

        $aliveKeeper = new FailoverAwareAliveKeeper(
            $loggerProphecy->reveal(),
            $connectionProphecy->reveal(),
            'default'
        );
        $aliveKeeper->keepAlive();
    }

    /**
     * @throws Exception
     */
    public function testKeepAliveReaderWithoutReconnect(): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $statementProphecy = $this->prophesize(Result::class);
        $statementProphecy->fetchOne()->willReturn('1')->shouldBeCalled();

        /** @var $connectionProphecy Connection|ObjectProphecy */
        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->executeQuery(Argument::any())->willReturn($statementProphecy->reveal())->shouldBeCalled();
        $connectionProphecy->close()->shouldNotBeCalled();
        $connectionProphecy->connect()->shouldNotBeCalled();

        $aliveKeeper = new FailoverAwareAliveKeeper(
            $loggerProphecy->reveal(),
            $connectionProphecy->reveal(),
            'default',
            ConnectionType::READER
        );
        $aliveKeeper->keepAlive();
    }

    /**
     * @throws Exception
     */
    public function testKeepAliveWriterWithReconnectOnFailover(): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->log(LogLevel::ALERT, Argument::any())->shouldBeCalled();
        $statementProphecy = $this->prophesize(Result::class);
        $statementProphecy->fetchOne()->willReturn('1')->shouldBeCalled();

        /** @var $connectionProphecy Connection|ObjectProphecy */
        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->executeQuery(Argument::any())->willReturn($statementProphecy->reveal())->shouldBeCalled();
        $connectionProphecy->close()->shouldBeCalled();
        $connectionProphecy->connect()->shouldBeCalled();

        $aliveKeeper = new FailoverAwareAliveKeeper(
            $loggerProphecy->reveal(),
            $connectionProphecy->reveal(),
            'default'
        );
        $aliveKeeper->keepAlive();
    }

    /**
     * @throws Exception
     */
    public function testKeepAliveReaderWithReconnectOnFailover(): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->log(LogLevel::WARNING, Argument::any())->shouldBeCalled();
        $statementProphecy = $this->prophesize(Result::class);
        $statementProphecy->fetchOne()->willReturn('0')->shouldBeCalled();

        /** @var $connectionProphecy Connection|ObjectProphecy */
        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->executeQuery(Argument::any())->willReturn($statementProphecy->reveal())->shouldBeCalled();
        $connectionProphecy->close()->shouldBeCalled();
        $connectionProphecy->connect()->shouldBeCalled();

        $aliveKeeper = new FailoverAwareAliveKeeper(
            $loggerProphecy->reveal(),
            $connectionProphecy->reveal(),
            'default',
            ConnectionType::READER
        );
        $aliveKeeper->keepAlive();
    }

    /**
     * @throws Exception
     */
    public function testKeepAliveWithReconnectConnectionError(): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->info(Argument::any(), Argument::any())->shouldBeCalled();
        $statementProphecy = $this->prophesize(Result::class);
        $statementProphecy->fetchOne()->willThrow(DriverException::class)->shouldBeCalled();

        /** @var $connectionProphecy Connection|ObjectProphecy */
        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->executeQuery(Argument::any())->willReturn($statementProphecy->reveal())->shouldBeCalled();
        $connectionProphecy->close()->shouldBeCalled();
        $connectionProphecy->connect()->shouldBeCalled();

        $aliveKeeper = new FailoverAwareAliveKeeper(
            $loggerProphecy->reveal(),
            $connectionProphecy->reveal(),
            'default'
        );
        $aliveKeeper->keepAlive();
    }
}
