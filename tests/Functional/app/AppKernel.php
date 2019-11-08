<?php
declare(strict_types=1);
/*
 * @author jhrncar
 */

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

/**
 */
final class AppKernel extends Kernel
{

    /**
     * @var string
     */
    private $varDir;

    /**
     * @var string
     */
    private $testCase;

    /**
     * @var string
     */
    private $rootConfig;

    /**
     * @param $varDir
     * @param $testCase
     * @param $rootConfig
     * @param $environment
     * @param $debug
     * @throws InvalidArgumentException
     */
    public function __construct($varDir, $testCase, $rootConfig, $environment, $debug)
    {
        if (!is_dir(__DIR__ . '/' . $testCase)) {
            throw new InvalidArgumentException(sprintf('The test case "%s" does not exist.', $testCase));
        }

        $this->varDir = $varDir;
        $this->testCase = $testCase;

        $filesystem = new Filesystem();
        if (!$filesystem->isAbsolutePath($rootConfig)
            && !is_file($rootConfig = __DIR__ . '/' . $testCase . '/' . $rootConfig)
        ) {
            throw new InvalidArgumentException(sprintf('The root config "%s" does not exist.', $rootConfig));

        }
        $this->rootConfig = $rootConfig;

        parent::__construct($environment, $debug);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        if (null === $this->name) {
            $this->name = parent::getName() . substr(md5($this->rootConfig), -16);
        }

        return $this->name;
    }

    /**
     * @return mixed|\Symfony\Component\HttpKernel\Bundle\BundleInterface[]
     * @throws RuntimeException
     */
    public function registerBundles()
    {
        if (!is_file($filename = $this->getRootDir() . '/config/bundles.php')) {
            throw new RuntimeException(sprintf('The bundles file "%s" does not exist.', $filename));
        }

        return include $filename;
    }

    /**
     * @return string
     */
    public function getRootDir(): string
    {
        return __DIR__;
    }

    /**
     * @return string
     */
    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/' . $this->varDir . '/' . $this->testCase . '/cache/' . $this->environment;
    }

    /**
     * @return string
     */
    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/' . $this->varDir . '/' . $this->testCase . '/logs';
    }

    /**
     * @param LoaderInterface $loader
     * @throws Exception
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load($this->rootConfig);
    }

    /**
     * @return string
     */
    public function serialize(): string
    {
        return serialize([
            $this->varDir,
            $this->testCase,
            $this->rootConfig,
            $this->getEnvironment(),
            $this->isDebug()
        ]);
    }

    /**
     * @param $str
     * @throws InvalidArgumentException
     */
    public function unserialize($str): void
    {
        $data = unserialize($str);
        $this->__construct($data[0], $data[1], $data[2], $data[3], $data[4]);
    }

    /**
     * @return array
     */
    protected function getKernelParameters(): array
    {
        $parameters = parent::getKernelParameters();
        $parameters['kernel.test_case'] = $this->testCase;

        return $parameters;
    }
}
