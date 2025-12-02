<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\Helper;

use Closure;
use Doctrine\DBAL\Connection;
use ProxyManager\Proxy\VirtualProxyInterface;

/**
 * @final
 */
// phpcs:ignore SlevomatCodingStandard.Classes.RequireAbstractOrFinal.ClassNeitherAbstractNorFinal
class ProxyConnectionMock extends Connection implements VirtualProxyInterface
{
    public function setProxyInitializer(?Closure $initializer = null): void
    {
    }

    public function getProxyInitializer(): ?Closure
    {
        return null;
    }

    public function initializeProxy(): bool
    {
        return false;
    }

    public function isProxyInitialized(): bool
    {
        return false;
    }

    public function getWrappedValueHolderValue(): ?object
    {
        return null;
    }

    public function isTransactionActive(): bool
    {
        return false;
    }

    public function rollBack(): void
    {
    }
}
