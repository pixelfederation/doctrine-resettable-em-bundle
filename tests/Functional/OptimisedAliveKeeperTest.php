<?php
declare(strict_types=1);
namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PixelFederation\DoctrineResettableEmBundle\Connection\AliveKeeper\OptimizedAliveKeeper;
use PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\OptimisedAliveKeeperTest\ConnectionMock;
use PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\OptimisedAliveKeeperTest\RedisClusterSpy;
use RedisCluster;
use ReflectionClass;

final class OptimisedAliveKeeperTest extends TestCase
{
    /**
     * @throws Exception
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::bootTestKernel();
    }

    protected static function getTestCase(): string
    {
        return 'OptimisedAliveKeeperTest';
    }

    public function testPingIntervalInjectionFromConfiguration(): void
    {
        $doctrineHandlerSvcId = sprintf('%s:doctrine_%s', OptimizedAliveKeeper::class, 'default');
        /* @var $handler OptimizedAliveKeeper */
        $handler = self::getContainer()->get($doctrineHandlerSvcId);
        $refl = new ReflectionClass(OptimizedAliveKeeper::class);
        $intervalParam = $refl->getProperty('pingIntervalInSeconds');
        $intervalParam->setAccessible(true);

        self::assertSame(10, $intervalParam->getValue($handler));

        $redisHandlerSvcId = sprintf('%s:redis_%s', OptimizedAliveKeeper::class, 'default');
        /* @var $handler OptimizedAliveKeeper */
        $handler = self::getContainer()->get($redisHandlerSvcId);

        self::assertSame(10, $intervalParam->getValue($handler));
    }

    public function testThatOnlyFirstPingWillBeMadeIn10SecondsOnRequestStart(): void
    {
        $client = self::createClient();

        /* @var $em EntityManagerInterface */
        $em = self::getContainer()->get('doctrine.orm.default_entity_manager');
        /** @var ConnectionMock $connection */
        $connection = $em->getConnection();
        /** @var RedisClusterSpy $redisCluster */
        $redisCluster = self::getContainer()->get(RedisCluster::class);

        self::assertFalse($connection->isConnected());
        self::assertSame(1, $redisCluster->getConstructorCalls());
        $connection->connect(); // simulates real connection usage
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
}
