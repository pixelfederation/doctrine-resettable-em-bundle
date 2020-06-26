<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\FailoverAwareTest;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;

/**
 */
final class ConnectionMock extends Connection
{
    /**
     * @var string
     */
    private $query;

    public function query()
    {
        $args = func_get_args();
        $this->query = $args[0];

        return new class extends Statement {
            public function __construct()
            {
            }

            public function fetchColumn($columnIndex = 0)
            {
                return '1';
            }
        };
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }
}
