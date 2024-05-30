<?php
declare(strict_types=1);
namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\OptimisedAliveKeeperTest;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PDO;

final class ConnectionMock extends Connection
{
    private array $queries = [];

    public function executeQuery(
        string $sql,
        array $params = [],
               $types = [],
        ?QueryCacheProfile $qcp = null
    ): Result {
        $args = func_get_args();
        $this->queries[] = $args[0];

        return new class extends Result {
            public function __construct()
            {
            }

            /**
             * @return mixed
             */
            public function fetchOne(): mixed
            {
                return '1';
            }

            /**
             * @return mixed
             */
            public function fetch($fetchMode = null, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
            {
                return 1;
            }
        };
    }

    public function getQueries(): array
    {
        return $this->queries;
    }

    public function getQueriesCount(): int
    {
        return count ($this->queries);
    }
}
