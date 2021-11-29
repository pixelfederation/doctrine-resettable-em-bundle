<?php

declare(strict_types=1);

/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\DependencyInjection\CompilerPass;

use PixelFederation\DoctrineResettableEmBundle\DBAL\Logging\ResettableDebugStack;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

final class DoctrineLoggerOverriderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('doctrine.connections')) {
            return;
        }

        /** @var array<string> $connectionNames */
        $connectionNames = array_keys((array) $container->getParameter('doctrine.connections'));

        foreach ($connectionNames as $connectionName) {
            $profilerSvcId = sprintf('doctrine.dbal.logger.profiling.%s', $connectionName);

            try {
                $profilerDef = $container->findDefinition($profilerSvcId);
            } catch (ServiceNotFoundException $e) {
                continue;
            }

            $profilerDef->setClass(ResettableDebugStack::class);
            $profilerDef->addTag('kernel.reset', ['method' => 'reset']);
        }
    }
}
