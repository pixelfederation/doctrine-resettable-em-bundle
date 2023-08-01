<?php
declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL\Connection;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\DBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\PassiveIgnoringDBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Tests\Unit\Helper\ProxyConnectionMock;
use ProxyManager\Proxy\VirtualProxyInterface;

class PassiveIgnoringDBALAliveKeeperTest extends TestCase
{
    public function testKeepAliveWithoutInitialisedConnectionProxyDoesNotDoAnything(): void
    {
        /** @var $connectionMock VirtualProxyInterface|Connection */
        $connectionMock = $this->createMock(ProxyConnectionMock::class);
        $connectionMock->expects(self::atLeast(1))
            ->method('isProxyInitialized')
            ->willReturn(false);
        $connectionMock->expects(self::exactly(0))
            ->method('getDatabasePlatform');
        $connectionName = 'default';

        $decoratedAliveKeeper = $this->createMock(DBALAliveKeeper::class);
        $decoratedAliveKeeper->expects(self::exactly(0))
            ->method('keepAlive')
            ->with($connectionMock, $connectionName);

        $aliveKeeper = new PassiveIgnoringDBALAliveKeeper(
            $decoratedAliveKeeper);
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
    }

    public function testKeepAliveWithoutInitialisedConnectionDoesNotDoAnything(): void
    {
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects(self::atLeast(1))
            ->method('isConnected')
            ->willReturn(false);
        $connectionMock->expects(self::exactly(0))
            ->method('getDatabasePlatform');
        $connectionName = 'default';

        $decoratedAliveKeeper = $this->createMock(DBALAliveKeeper::class);
        $decoratedAliveKeeper->expects(self::exactly(0))
            ->method('keepAlive')
            ->with($connectionMock, $connectionName);

        $aliveKeeper = new PassiveIgnoringDBALAliveKeeper(
            $decoratedAliveKeeper);
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
    }

    public function testKeepAliveWithInitialisedConnectionDelegatesControl(): void
    {
        /** @var $connectionMock VirtualProxyInterface|Connection */
        $connectionMock = $this->createMock(ProxyConnectionMock::class);
        $connectionMock->expects(self::atLeast(1))
            ->method('isProxyInitialized')
            ->willReturn(true);
        $connectionMock->expects(self::atLeast(1))
            ->method('isConnected')
            ->willReturn(true);
        $connectionMock->expects(self::exactly(0))
            ->method('getDatabasePlatform');
        $connectionName = 'default';

        $decoratedAliveKeeper = $this->createMock(DBALAliveKeeper::class);
        $decoratedAliveKeeper->expects(self::atLeast(1))
            ->method('keepAlive')
            ->with($connectionMock, $connectionName);

        $aliveKeeper = new PassiveIgnoringDBALAliveKeeper($decoratedAliveKeeper);
        $aliveKeeper->keepAlive($connectionMock, $connectionName);
    }
}
