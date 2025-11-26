<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\FailoverAwareTest;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;

final class ConnectionMock extends Connection
{
    private string $query;

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
        $this->query = $args[0];

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

    public function getQuery(): string
    {
        return $this->query;
    }
}
