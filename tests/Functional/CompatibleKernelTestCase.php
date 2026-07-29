<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

if (method_exists(KernelTestCase::class, 'runCommand')) {
    abstract class CompatibleKernelTestCase extends KernelTestCase
    {
    }
} else {
    abstract class CompatibleKernelTestCase extends KernelTestCase
    {
        /**
         * Symfony 7.4 compatibility for KernelTestCase::runCommand(), which was added in Symfony 8.1.
         *
         * @param array<string, mixed> $input
         * @param string[] $interactiveInputs
         * @param array<\Closure(string): string> $normalizers
         */
        public static function runCommand(
            string $name,
            array $input = [],
            array $interactiveInputs = [],
            ?bool $interactive = null,
            ?bool $decorated = null,
            ?int $verbosity = null,
            array $normalizers = [],
        ): int {
            $application = new Application(static::getContainer()->get('kernel'));
            $commandTester = new CommandTester($application->find($name));
            $commandTester->setInputs($interactiveInputs);

            return $commandTester->execute(
                $input,
                array_filter(
                    [
                        'interactive' => $interactive,
                        'decorated' => $decorated,
                        'verbosity' => $verbosity,
                    ],
                    static fn (mixed $value): bool => $value !== null,
                ),
            );
        }
    }
}
