<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection;

use Doctrine\DBAL\Connection;

interface AliveKeeper
{
    public function keepAlive(Connection $connection, string $connectionName): void;
}
