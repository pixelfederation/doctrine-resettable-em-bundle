<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\OptimisedAliveKeeperTest;

use Override;
use RedisCluster;
use SensitiveParameter;

/**
 * @final
 */
// phpcs:ignore SlevomatCodingStandard.Classes.RequireAbstractOrFinal.ClassNeitherAbstractNorFinal
class RedisClusterSpy extends RedisCluster
{
    private int $constructorCalls = 0;

    private int $pingCount = 0;

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

//        parent::__construct($name, $seeds, $timeout, $readTimeout, $persistent, $auth, $context);
    }

    public function getConstructorCalls(): int
    {
        return $this->constructorCalls;
    }

    #[Override]
    // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    public function ping(array|string $key_or_address, ?string $message = null): mixed
    {
        return $this->pingCount++;
    }

    public function getPingCount(): int
    {
        return $this->pingCount;
    }
}
