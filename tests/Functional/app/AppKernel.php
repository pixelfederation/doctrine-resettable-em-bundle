<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app;

use InvalidArgumentException;
use Override;
use RuntimeException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

final class AppKernel extends Kernel
{
    private readonly string $rootConfig;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        private readonly string $varDir,
        private readonly string $testCase,
        string $rootConfig,
        string $environment,
        bool $debug,
    ) {
        if (!is_dir(__DIR__ . '/' . $testCase)) {
            throw new InvalidArgumentException(sprintf('The test case "%s" does not exist.', $testCase));
        }

        $filesystem = new Filesystem();
        if (!$filesystem->isAbsolutePath($rootConfig)) {
            $rootConfig = __DIR__ . '/' . $testCase . '/' . $rootConfig;
        }
        if (!is_file($rootConfig)) {
            throw new InvalidArgumentException(sprintf('The root config "%s" does not exist.', $rootConfig));
        }
        $this->rootConfig = $rootConfig;

        parent::__construct($environment, $debug);
    }

    #[Override]
    public function getProjectDir(): string
    {
        return __DIR__;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function registerBundles(): iterable
    {
        $filename = $this->getRootDir() . '/config/bundles.php';
        if (!is_file($filename)) {
            throw new RuntimeException(sprintf('The bundles file "%s" does not exist.', $filename));
        }

        return include $filename;
    }

    public function getRootDir(): string
    {
        return __DIR__;
    }

    #[Override]
    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/' . $this->varDir . '/' . $this->testCase . '/cache/' . $this->environment;
    }

    #[Override]
    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/' . $this->varDir . '/' . $this->testCase . '/logs';
    }

    #[Override]
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load($this->rootConfig);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function getKernelParameters(): array
    {
        $parameters = parent::getKernelParameters();
        $parameters['kernel.test_case'] = $this->testCase;

        return $parameters;
    }
}
