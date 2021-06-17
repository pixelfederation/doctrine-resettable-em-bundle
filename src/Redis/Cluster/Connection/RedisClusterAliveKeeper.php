<?php

declare(strict_types=1);

/*
 * @author    mfris
 * @copyright PIXELFEDERATION s.r.o.
 * @license   Internal use only
 */

namespace PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection;

use PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper\AliveKeeper;
use Psr\Log\LoggerInterface;
use RedisCluster;
use RedisClusterException;

final class RedisClusterAliveKeeper implements AliveKeeper
{
    private string $connectionName;

    private RedisCluster $redis;

    /**
     * @var array{
     *   0: string|null,
     *   1: array,
     *   2: float,
     *   3: float,
     *   4: bool,
     *   5: string|null
     * }
     */
    private array $constructorArguments;

    private LoggerInterface $logger;

    /**
     * @param RedisCluster    $redis
     * @param array{
     *   0: string|null,
     *   1: array,
     *   2: float,
     *   3: float,
     *   4: bool,
     *   5: string|null
     * } $constructorArguments
     * @param LoggerInterface $logger
     * @param string          $connectionName
     */
    public function __construct(
        string $connectionName,
        RedisCluster $redis,
        array $constructorArguments,
        LoggerInterface $logger
    ) {
        $this->connectionName = $connectionName;
        $this->redis = $redis;
        $this->constructorArguments = $constructorArguments;
        $this->logger = $logger;
    }

    public function keepAlive(): void
    {
        try {
            $this->redis->ping('hello');
        } catch (RedisClusterException $e) {
            $this->logger->info(
                sprintf("Exceptional reconnect for redis cluster connection '%s'", $this->connectionName),
                [
                    'exception' => $e,
                ]
            );
            // redis cluster does not have a reconnect method and does not work with shard master to slave failover,
            // so this hack has to be used
            call_user_func_array([$this->redis, '__construct'], $this->constructorArguments);
        }
    }
}
