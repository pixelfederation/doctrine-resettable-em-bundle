<?php
declare(strict_types=1);
namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional;

use Exception;
use InvalidArgumentException;
use PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\AppKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class TestCase extends KernelTestCase
{
    private static ?Application $application = null;

    /**
     * @throws IOException
     */
    public static function setUpBeforeClass(): void
    {
        static::deleteTmpDir();
    }

    /**
     * @throws IOException
     */
    public static function tearDownAfterClass(): void
    {
        static::deleteTmpDir();
    }

    /**
     * @throws Exception
     */
    protected function tearDown():void
    {
        parent::tearDown();

        if (self::$application !== null) {
            self::$application = null;
        }
    }

    /**
     * @throws IOException
     */
    protected static function deleteTmpDir(): void
    {
        if (!file_exists($dir = sys_get_temp_dir() . '/' . static::getVarDir())) {
            return;
        }

        $filesystem = new Filesystem();
        $filesystem->remove($dir);
    }

    /**
     * @inheritdoc
     */
    protected static function getKernelClass(): string
    {
        require_once __DIR__ . '/app/AppKernel.php';

        return AppKernel::class;
    }

    /**
     * @inheritdoc
     * @throws InvalidArgumentException
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        $class = self::getKernelClass();

        if (!isset($options['test_case'])) {
            throw new InvalidArgumentException('The option "test_case" must be set.');
        }

        return new $class(
            static::getVarDir(),
            $options['test_case'],
            $options['root_config'] ?? 'config.yml',
            $options['environment'] ?? strtolower(static::getVarDir() . $options['test_case']),
            $options['debug'] ?? true
        );
    }

    /**
     * Creates a Client.
     *
     * @param array $server An array of server parameters
     */
    protected static function createClient(array $server = [], string $rootConfig = 'configs/config.yaml'): KernelBrowser
    {
        static::bootTestKernel($rootConfig);

        $client = self::getContainer()->get('test.client');
        $client->setServerParameters($server);

        return $client;
    }

    /**
     * @throws Exception
     */
    protected static function runCommand(string $command): void
    {
        $command = sprintf('%s --quiet', $command);
        self::getApplication()->run(new StringInput($command));
    }

    protected static function bootTestKernel(string $rootConfig = 'configs/config.yaml'): void
    {
        self::bootKernel(['test_case' => static::getTestCase(), 'root_config' => $rootConfig]);
    }

    abstract protected static function getTestCase(): string;

    protected static function getApplication(): Application
    {
        if (self::$application === null) {
            self::$application = new Application(self::$kernel);
            self::$application->setAutoExit(false);
        }

        return self::$application;
    }

    protected static function getVarDir(): string
    {
        return 'PFCBB' . substr(strrchr(static::class, '\\'), 1);
    }
}
