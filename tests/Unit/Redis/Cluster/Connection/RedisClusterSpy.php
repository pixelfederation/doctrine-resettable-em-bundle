<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\Redis\Cluster\Connection;

use Override;
use RedisCluster;
use RedisClusterException;
use SensitiveParameter;
use Symfony\Component\VarExporter\LazyObjectInterface;

//phpcs:disable
/**
 * @final
 */
class RedisClusterSpy extends RedisCluster implements LazyObjectInterface
{
    private int $constructorCalls = 0;

    private bool $wasConstructorCalled = false;

    /**
     * @var array<mixed>
     */
    private array $constructorParametersFirst = [];

    /**
     * @var array<mixed>
     */
    private array $constructorParametersSecond = [];

    private bool $initialized = true;

    /**
     * @inheritDoc
     */
    public function __construct(
        ?string $name,
        ?array $seeds,
        int|float $timeout = 0,
        int|float $readTimeout = 0,
        bool $persistent = false,
        #[SensitiveParameter]
        mixed $auth = null,
        ?array $context = null,
    ) {
        $this->constructorCalls++;

        if ($this->constructorCalls === 1) {
            $this->constructorParametersFirst = [$name, $seeds, $timeout, $readTimeout];
        } elseif ($this->constructorCalls > 1) {
            $this->wasConstructorCalled = true;
            $this->constructorParametersSecond = [$name, $seeds, $timeout, $readTimeout];
        }
//        parent::__construct($name, $seeds, $timeout, $readTimeout, $persistent, $auth, $context);
    }

    public function wasConstructorCalled(): bool
    {
        return $this->wasConstructorCalled;
    }

    /**
     * @return array<mixed>
     */
    public function getConstructorParametersFirst(): array
    {
        return $this->constructorParametersFirst;
    }

    /**
     * @return array<mixed>
     */
    public function getConstructorParametersSecond(): array
    {
        return $this->constructorParametersSecond;
    }

    #[Override]
    public function ping(
        array|string $key_or_address,
        ?string $message = null,
    ): mixed {
        throw new RedisClusterException('Test exception');
    }

    public function setIsProxyInitialized(bool $initialised = true): void
    {
        $this->initialized = $initialised;
    }

    public function getWrappedValueHolderValue(): ?object
    {
        return null;
    }

    public function isLazyObjectInitialized(bool $partial = false): bool
    {
        return $this->initialized;
    }

    public function initializeLazyObject(): object
    {
        return $this;
    }

    public function resetLazyObject(): bool
    {
        return false;
    }
}
