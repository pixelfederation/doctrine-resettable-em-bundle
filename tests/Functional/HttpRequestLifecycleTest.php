<?php
declare(strict_types=1);
/*
 * @author     mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PixelFederation\DoctrineResettableEmBundle\ORM\ResettableEntityManager;
use PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\HttpRequestLifecycleTest\EntityManagerChecker;

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
    }

    protected static function getTestCase(): string
    {
        return 'HttpRequestLifecycleTest';
    }

    public function testPingEmConnectionsOnRequestStart(): void
    {
        $client = self::createClient();

        /* @var $em EntityManagerInterface */
        $em = self::$container->get('doctrine.orm.default_entity_manager');
        $connection = $em->getConnection();

        self::assertFalse($connection->isConnected());
        $client->request('GET', '/dummy'); // this action does nothing with the database
        self::assertTrue($connection->isConnected());
    }

    /**
     * @throws Exception
     */
    public function testEmWillBeResetWithServicesResetter(): void
    {
        /* @var $em EntityManagerInterface */
        $em = self::$container->get('doctrine.orm.default_entity_manager');
        self::assertInstanceOf(ResettableEntityManager::class, $em);

        $client = self::createClient();
        $checker = $client->getContainer()->get(EntityManagerChecker::class);
        $client->disableReboot();
        $client->request('GET', '/');

        self::assertSame(1, $checker->getNumberOfChecks());
        self::assertTrue($checker->wasEmptyOnLastCheck());

        $client->request('GET', '/');

        self::assertSame(2, $checker->getNumberOfChecks());
        self::assertTrue($checker->wasEmptyOnLastCheck());
    }
}
