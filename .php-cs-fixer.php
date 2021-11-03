<?php

declare(strict_types=1);

$config = (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        'array_syntax'      => [
            'syntax' => 'short',
        ],
        'no_useless_else'       => true,
        'no_useless_return'     => true,
        'strict_comparison'     => true,
        'strict_param'          => true,
        'declare_strict_types'  => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude('to-remove')
            ->exclude('vendor')
            ->in(__DIR__ . '/src')
            ->in(__DIR__ . '/tests')
    );

return $config;
