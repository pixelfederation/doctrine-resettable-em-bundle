<?php
declare(strict_types=1);
/*
 * @author     mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PixelFederation\DoctrineResettableEmBundle\ORM\ResettableEntityManager;

/**
 */
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

    /**
     * @return string
     */
    protected static function getTestCase(): string
    {
        return 'HttpRequestLifecycleTest';
    }

    /**
     *
     */
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
    public function testEmWillBeResetOnRequestEnd(): void
    {
        $client = self::createClient();
        $client->request('GET', '/');
        /* @var $em EntityManagerInterface */
        $em = self::$container->get('doctrine.orm.default_entity_manager');
        $uow = $em->getUnitOfWork();
        self::assertInstanceOf(ResettableEntityManager::class, $em);
        self::assertEmpty($uow->getIdentityMap());
    }
}
