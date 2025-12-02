<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\Redis\Cluster\Connection;

use Closure;
use Override;
use ProxyManager\Proxy\VirtualProxyInterface;
use RedisCluster;
use RedisClusterException;
use SensitiveParameter;

/**
 * @final
 */
// phpcs:ignore SlevomatCodingStandard.Classes.RequireAbstractOrFinal.ClassNeitherAbstractNorFinal
class RedisClusterSpy extends RedisCluster implements VirtualProxyInterface
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
    // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    public function ping(array|string $key_or_address, ?string $message = null): mixed
    {
        throw new RedisClusterException('Test exception');
    }

    #[Override]
    public function setProxyInitializer(?Closure $initializer = null): void
    {
    }

    #[Override]
    public function getProxyInitializer(): ?Closure
    {
        return null;
    }

    #[Override]
    public function initializeProxy(): bool
    {
        return true;
    }

    #[Override]
    public function isProxyInitialized(): bool
    {
        return $this->initialized;
    }

    public function setIsProxyInitialized(bool $initialised = true): void
    {
        $this->initialized = $initialised;
    }

    #[Override]
    public function getWrappedValueHolderValue(): ?object
    {
        return null;
    }
}
