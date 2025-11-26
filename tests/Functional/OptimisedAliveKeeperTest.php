<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Override;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\OptimizedDBALAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Redis\Cluster\Connection\OptimizedRedisClusterAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\OptimisedAliveKeeperTest\ConnectionMock;
use PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\OptimisedAliveKeeperTest\RedisClusterSpy;
use RedisCluster;
use ReflectionClass;

final class OptimisedAliveKeeperTest extends TestCase
{
    public function testPingIntervalInjectionFromConfiguration(): void
    {
        $doctrineHandlerSvcId = sprintf('%s_%s', OptimizedDBALAliveKeeper::class, 'default');
        $handler = self::getContainer()->get($doctrineHandlerSvcId);
        self::assertInstanceOf(OptimizedDBALAliveKeeper::class, $handler);
        $refl = new ReflectionClass(OptimizedDBALAliveKeeper::class);
        $intervalParam = $refl->getProperty('pingIntervalInSeconds');
        $intervalParam->setAccessible(true);

        self::assertSame(10, $intervalParam->getValue($handler));

        $redisHandlerSvcId = sprintf('%s_%s', OptimizedRedisClusterAliveKeeper::class, 'default');
        $handler = self::getContainer()->get($redisHandlerSvcId);
        self::assertInstanceOf(OptimizedRedisClusterAliveKeeper::class, $handler);
        $refl2 = new ReflectionClass(OptimizedRedisClusterAliveKeeper::class);
        $intervalParam2 = $refl2->getProperty('pingIntervalInSeconds');
        $intervalParam2->setAccessible(true);

        self::assertSame(10, $intervalParam2->getValue($handler));
    }

    public function testThatOnlyFirstPingWillBeMadeIn10SecondsOnRequestStart(): void
    {
        $client = self::createClient();

        $em = self::getContainer()->get('doctrine.orm.default_entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        $connection = $em->getConnection();
        self::assertInstanceOf(ConnectionMock::class, $connection);
        $redisCluster = self::getContainer()->get(RedisCluster::class);
        self::assertInstanceOf(RedisClusterSpy::class, $redisCluster);

        self::assertFalse($connection->isConnected());
        self::assertSame(1, $redisCluster->getConstructorCalls());
        $connection->getNativeConnection(); // simulates real connection usage
        $client->request('GET', '/dummy'); // this action does nothing with the database
        self::assertTrue($connection->isConnected());
        self::assertSame(1, $connection->getQueriesCount());
        self::assertSame(1, $redisCluster->getConstructorCalls());
        self::assertSame(1, $redisCluster->getPingCount());

        $client->request('GET', '/dummy'); // this action does nothing with the database
        self::assertSame(1, $connection->getQueriesCount());
        self::assertSame(1, $redisCluster->getConstructorCalls());
        self::assertSame(1, $redisCluster->getPingCount());
    }

    #[Override]
    protected static function getTestCase(): string
    {
        return 'OptimisedAliveKeeperTest';
    }

    #[Override]
    protected function setUp(): void
    {
        self::bootTestKernel();
    }
}
