<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\OptimisedAliveKeeperTest;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;

final class ConnectionMock extends Connection
{
    /**
     * @var array<string>
     */
    private array $queries = [];

    /**
     * @inheritDoc
     */
    public function executeQuery(
        string $sql,
        array $params = [],
        $types = [],
        ?QueryCacheProfile $qcp = null,
    ): Result {
        $args = func_get_args();
        $this->queries[] = $args[0];

        return new class extends Result {
            public function __construct()
            {
            }

            public function fetchOne(): string
            {
                return '1';
            }

            public function fetchNumeric(): int
            {
                return 1;
            }
        };
    }

    /**
     * @return array<string>
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    public function getQueriesCount(): int
    {
        return count($this->queries);
    }
}
