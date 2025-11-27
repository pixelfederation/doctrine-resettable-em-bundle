<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use PixelFederation\DoctrineResettableEmBundle\ORM\ResettableEntityManager;
use PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\HttpRequestLifecycleTest\ConnectionMock;
use PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\HttpRequestLifecycleTest\EntityManagerChecker;
use PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\HttpRequestLifecycleTest\RedisClusterSpy;
use RedisCluster;
use ReflectionClass;
use Symfony\Component\HttpKernel\Kernel;

final class HttpRequestLifecycleTest extends TestCase
{
    public function testDoNotPingConnectionsOnRequestStartIfConnectionIsNotOpen(): void
    {
        $this->setUpInternal();
        $client = self::createClient();

        $em = self::getContainer()->get('doctrine.orm.default_entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        $connection = $em->getConnection();
        $redisCluster = self::getContainer()->get(RedisCluster::class);
        self::assertInstanceOf(RedisClusterSpy::class, $redisCluster);
        $redisCluster->setIsProxyInitialized(false);

        self::assertFalse($connection->isConnected());
        self::assertFalse($redisCluster->wasConstructorCalled());
        $client->request('GET', '/dummy'); // this action does nothing with the database
        self::assertFalse($connection->isConnected());
        // redis cluster does not provide conenction instance without creating the connection
        self::assertFalse($redisCluster->wasConstructorCalled());
    }

    public function testPingConnectionsOnRequestStart(): void
    {
        $this->setUpInternal('configs/config-conn-mock.yaml');
        $client = self::createClient([], 'configs/config-conn-mock.yaml');

        $em = self::getContainer()->get('doctrine.orm.default_entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        $connection = $em->getConnection();
        self::assertInstanceOf(ConnectionMock::class, $connection);
        $emExcluded = self::getContainer()->get('doctrine.orm.excluded_entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $emExcluded);
        $connectionExcluded = $emExcluded->getConnection();
        self::assertInstanceOf(ConnectionMock::class, $connectionExcluded);
        $redisCluster = self::getContainer()->get(RedisCluster::class);
        self::assertInstanceOf(RedisClusterSpy::class, $redisCluster);
        $redisClusterExcluded = self::getContainer()->get(RedisCluster::class . '2');
        self::assertInstanceOf(RedisClusterSpy::class, $redisClusterExcluded);

        self::assertFalse($connection->isConnected());
        self::assertFalse($connectionExcluded->isConnected());
        self::assertFalse($redisCluster->wasConstructorCalled());
        self::assertFalse($redisClusterExcluded->wasConstructorCalled());
        $connection->getNativeConnection(); // simulates real connection usage
        $connectionExcluded->getNativeConnection(); // simulates real connection usage
        $client->request('GET', '/dummy'); // this action does nothing with the database
        self::assertTrue($connection->isConnected());
        self::assertSame('SELECT 1', $connection->getQuery());
        self::assertNull($connectionExcluded->getQuery());
        self::assertTrue($connectionExcluded->isConnected());
        self::assertTrue($redisCluster->wasConstructorCalled());
        self::assertSame(
            $redisCluster->getConstructorParametersFirst(),
            $redisCluster->getConstructorParametersSecond(),
        );
        self::assertFalse($redisClusterExcluded->wasConstructorCalled());
    }

    public function testCheckIfConnectionsHaveActiveTransactionsOnRequestStart(): void
    {
        $this->setUpInternal('configs/config-trans-check.yaml');
        $client = self::createClient([], 'configs/config-trans-check.yaml');

        $em = self::getContainer()->get('doctrine.orm.default_entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        $connection = $em->getConnection();
        $emExcluded = self::getContainer()->get('doctrine.orm.excluded_entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $emExcluded);
        $connectionExcluded = $emExcluded->getConnection();

        self::assertFalse($connection->isConnected());
        self::assertFalse($connectionExcluded->isConnected());
        $connection->beginTransaction();
        $connectionExcluded->beginTransaction();
        self::assertTrue($connection->isTransactionActive());
        self::assertTrue($connectionExcluded->isTransactionActive());
        $client->request('GET', '/dummy'); // this action does nothing with the database
        self::assertTrue($connection->isConnected());
        self::assertFalse($connection->isTransactionActive());
        self::assertTrue($connectionExcluded->isConnected());
        self::assertTrue($connectionExcluded->isTransactionActive());
    }

    public function testEmWillBeResetWithServicesResetter(): void
    {
        $this->setUpInternal();
        $em = self::getContainer()->get('doctrine.orm.default_entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        self::assertInstanceOf(ResettableEntityManager::class, $em);

        $client = self::createClient();
        $checker = $client->getContainer()->get(EntityManagerChecker::class . '.default');
        $client->disableReboot();
        $client->request('GET', '/');

        self::assertSame(1, $checker->getNumberOfChecks());
        self::assertTrue($checker->wasEmptyOnLastCheck());

        $client->request('GET', '/');

        self::assertSame(2, $checker->getNumberOfChecks());
        self::assertTrue($checker->wasEmptyOnLastCheck());
    }

    public function testEmWillBeResetOnErrorWithServicesResetter(): void
    {
        $this->setUpInternal();
        $em = self::getContainer()->get('doctrine.orm.default_entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        self::assertInstanceOf(ResettableEntityManager::class, $em);
        $refl = new ReflectionClass(ResettableEntityManager::class);
        $wrappedProperty = $refl->getProperty('wrapped');
        $wrappedProperty->setAccessible(true);
        $wrapped = $wrappedProperty->getValue($em);

        $client = self::createClient();
        $checker = $client->getContainer()->get(EntityManagerChecker::class . '.default');
        self::assertInstanceOf(EntityManagerChecker::class, $checker);
        $client->disableReboot();
        $client->request('GET', '/persist-error');

        self::assertSame(1, $checker->getNumberOfChecks());
        self::assertTrue($checker->wasEmptyOnLastCheck());

        $client->request('GET', '/persist-error');

        self::assertSame(2, $checker->getNumberOfChecks());
        self::assertTrue($checker->wasEmptyOnLastCheck());

        $client->request('GET', '/persist-error');

        self::assertSame(3, $checker->getNumberOfChecks());
        self::assertTrue($checker->wasEmptyOnLastCheck());

        $wrapped2 = $wrappedProperty->getValue($em);
        self::assertInstanceOf(EntityManagerInterface::class, $wrapped2);
        self::assertSame($wrapped, $wrapped2);
        self::assertTrue($wrapped2->isOpen());

        $response = $client->request('GET', '/remove-all');

        self::assertSame(4, $checker->getNumberOfChecks());
        self::assertTrue($checker->wasEmptyOnLastCheck());
        if (Kernel::VERSION_ID < 70400) {
            self::assertSame(0, $response->count()); // this means that there was an empty response

            return;
        }
        self::assertSame('<head></head><body></body>', $response->html());
    }

    public function testExcludedEmWontBeWrappedAndWillBeResetWithDefaultDoctrineServicesResetter(): void
    {
        $this->setUpInternal();

        $em = self::getContainer()->get('doctrine.orm.excluded_entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        self::assertInstanceOf(EntityManager::class, $em);

        $client = self::createClient();
        $checker = $client->getContainer()->get(EntityManagerChecker::class . '.excluded');
        $client->disableReboot();
        $client->request('GET', '/persist-excluded');

        self::assertSame(1, $checker->getNumberOfChecks());
        self::assertTrue($checker->wasEmptyOnLastCheck());

        $client->request('GET', '/persist-excluded');

        self::assertSame(2, $checker->getNumberOfChecks());
        self::assertTrue($checker->wasEmptyOnLastCheck());
    }

    #[Override]
    protected static function getTestCase(): string
    {
        return 'HttpRequestLifecycleTest';
    }

    #[Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        self::deleteTmpDir();
    }

    private function setUpInternal(string $rootConfig = 'configs/config.yaml'): void
    {
        self::bootTestKernel($rootConfig);
        self::runCommand('cache:clear --no-warmup');
        self::runCommand('cache:warmup');
        self::runCommand('doctrine:database:drop --force --connection default');
        self::runCommand('doctrine:schema:create --em default');
        self::runCommand('doctrine:database:drop --force --connection excluded');
        self::runCommand('doctrine:schema:create --em excluded');
    }
}
