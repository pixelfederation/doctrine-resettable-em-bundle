<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\Helper;

use Doctrine\DBAL\Connection;
use Symfony\Component\VarExporter\LazyObjectInterface;

/**
 * @final
 */
// phpcs:ignore SlevomatCodingStandard.Classes.RequireAbstractOrFinal.ClassNeitherAbstractNorFinal
class ProxyConnectionMock extends Connection implements LazyObjectInterface
{
    public function isLazyObjectInitialized(bool $partial = false): bool
    {
        return false;
    }

    /**
     * Forces initialization of a lazy object and returns it.
     */
    public function initializeLazyObject(): object
    {
        return $this;
    }

    public function resetLazyObject(): bool
    {
        return false;
    }

    public function isTransactionActive(): bool
    {
        return false;
    }

    public function rollBack(): void
    {
    }
}
