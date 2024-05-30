<?php
declare(strict_types=1);
namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\FailoverAwareTest\ConnectionMock;

final class FailoverAwareTest extends TestCase
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
        return 'FailoverAwareTest';
    }

    public function testFailoverAliveKeeperOnRequestStartIsNotActivatedIfConnectionIsNotOpen(): void
    {
        $client = self::createClient();

        /* @var $em EntityManagerInterface */
        $em = self::getContainer()->get('doctrine.orm.default_entity_manager');
        $connection = $em->getConnection();
        self::assertInstanceOf(ConnectionMock::class, $connection);

        self::assertFalse($connection->isConnected());
        $client->request('GET', '/dummy'); // this action does nothing with the database
        self::assertFalse($connection->isConnected());
    }

    public function testFailoverAliveKeeperOnRequestStart(): void
    {
        $client = self::createClient();

        /* @var $em EntityManagerInterface */
        $em = self::getContainer()->get('doctrine.orm.default_entity_manager');
        $connection = $em->getConnection();
        self::assertInstanceOf(ConnectionMock::class, $connection);

        self::assertFalse($connection->isConnected());
        $connection->beginTransaction();
        self::assertTrue($connection->isTransactionActive());
        $client->request('GET', '/dummy'); // this action does nothing with the database
        self::assertTrue($connection->isConnected());
        self::assertSame('SELECT @@global.innodb_read_only;', $connection->getQuery());
        self::assertFalse($connection->isTransactionActive());
    }
}
