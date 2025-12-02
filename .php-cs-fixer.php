<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'declare_strict_types' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'strict_comparison' => true,
        'strict_param' => true,
    ])
    ->setFinder(
        Finder::create()
            ->exclude('vendor')
            ->in(__DIR__ . '/src')
            ->in(__DIR__ . '/tests'),
    );
