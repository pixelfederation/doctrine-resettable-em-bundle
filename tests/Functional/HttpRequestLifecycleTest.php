<?php
declare(strict_types=1);
namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PixelFederation\DoctrineResettableEmBundle\ORM\ResettableEntityManager;
use PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\HttpRequestLifecycleTest\EntityManagerChecker;
use RedisCluster;
use ReflectionClass;
use Symfony\Component\HttpKernel\Kernel;

final class HttpRequestLifecycleTest extends TestCase
{
    /**
     * @throws Exception
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::bootTestKernel();
        self::runCommand('cache:clear --no-warmup');
        self::runCommand('cache:warmup');
        self::runCommand('doctrine:database:drop --force --connection default');
        self::runCommand('doctrine:schema:create --em default');
        self::runCommand('doctrine:database:drop --force --connection excluded');
        self::runCommand('doctrine:schema:create --em excluded');
    }

    protected static function getTestCase(): string
    {
        return 'HttpRequestLifecycleTest';
    }

    public function testPingConnectionsOnRequestStart(): void
    {
        $client = self::createClient();

        /* @var $em EntityManagerInterface */
        $em = self::getContainer()->get('doctrine.orm.default_entity_manager');
        $connection = $em->getConnection();
        $redisCluster = self::getContainer()->get(RedisCluster::class);

        self::assertFalse($connection->isConnected());
        self::assertFalse($redisCluster->wasConstructorCalled());
        $client->request('GET', '/dummy'); // this action does nothing with the database
        self::assertTrue($connection->isConnected());
        self::assertTrue($redisCluster->wasConstructorCalled());
        self::assertSame(
            $redisCluster->getConstructorParametersFirst(),
            $redisCluster->getConstructorParametersSecond()
        );
    }

    /**
     * @throws Exception
     */
    public function testEmWillBeResetWithServicesResetter(): void
    {
        /* @var $em EntityManagerInterface */
        $em = self::getContainer()->get('doctrine.orm.default_entity_manager');
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

    /**
     * @throws Exception
     */
    public function testEmWillBeResetOnErrorWithServicesResetter(): void
    {
        /* @var $em EntityManagerInterface */
        $em = self::getContainer()->get('doctrine.orm.default_entity_manager');
        self::assertInstanceOf(ResettableEntityManager::class, $em);
        $refl = new ReflectionClass(ResettableEntityManager::class);
        $wrappedProperty = $refl->getProperty('wrapped');
        $wrappedProperty->setAccessible(true);
        $wrapped = $wrappedProperty->getValue($em);

        $client = self::createClient();
        $checker = $client->getContainer()->get(EntityManagerChecker::class . '.default');
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

        /** @var EntityManagerInterface $wrapped2 */
        $wrapped2 = $wrappedProperty->getValue($em);
        self::assertSame($wrapped, $wrapped2);
        self::assertTrue($wrapped2->isOpen());

        $response = $client->request('GET', '/remove-all');

        self::assertSame(4, $checker->getNumberOfChecks());
        self::assertTrue($checker->wasEmptyOnLastCheck());
        self::assertSame(0, $response->count()); // this means that there was an empty response
    }

    /**
     * @throws Exception
     */
    public function testExcludedEmWillBeResetOnErrorWithServicesResetterButRepositoryWontBeResetted(): void
    {
        if (version_compare(Kernel::VERSION, '6.2.0') >= 0) {
            $this->markTestSkipped('This test is not needed with Symfony 6.2');

            return;
        }

        /* @var $em EntityManagerInterface */
        $em = self::getContainer()->get('doctrine.orm.excluded_entity_manager');
        self::assertNotInstanceOf(ResettableEntityManager::class, $em);

        $client = self::createClient();
        $checker = $client->getContainer()->get(EntityManagerChecker::class . '.excluded');
        $client->disableReboot();
        $client->request('GET', '/persist-error-excluded');

        self::assertSame(1, $checker->getNumberOfChecks());
        self::assertTrue($checker->wasEmptyOnLastCheck());

        $client->request('GET', '/persist-error-excluded');

        self::assertSame(2, $checker->getNumberOfChecks());
        self::assertTrue($checker->wasEmptyOnLastCheck());

        $response = $client->request('GET', '/remove-all-excluded');

        self::assertTrue($checker->wasEmptyOnLastCheck());
        self::assertNotSame(0, $response->count());
        self::assertStringContainsString(
            'Detached entity PixelFederation\\DoctrineResettableEmBundle\\Tests\\Functional\\app\\HttpRequestLifecycleTest\\ExcludedEntity\\ExcludedTestEntity2',
            $response->html()
        );
    }

    /**
     * @throws Exception
     */
    public function testExcludedEmWontBeWrappedAndWillBeResetWithDefaultDoctrineServicesResetter(): void
    {
        /* @var $em EntityManagerInterface */
        $em = self::getContainer()->get('doctrine.orm.excluded_entity_manager');
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
}
