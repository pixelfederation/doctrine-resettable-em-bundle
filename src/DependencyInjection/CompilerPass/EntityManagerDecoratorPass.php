<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DependencyInjection\CompilerPass;

use PixelFederation\DoctrineResettableEmBundle\DependencyInjection\Parameters;
use PixelFederation\DoctrineResettableEmBundle\ORM\ResettableEntityManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class EntityManagerDecoratorPass implements CompilerPassInterface
{
    //phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    public function process(ContainerBuilder $container): void
    {
        // @var array<string, string> $entityManagers

        $entityManagers = $container->getParameter('doctrine.entity_managers');
        // @var array<string> $excluded

        $excluded = $container->getParameter(Parameters::EXCLUDED_FROM_PROCESSING_ENTITY_MANAGERS);
        $resettableEntityManagers = [];

        foreach ($entityManagers as $name => $id) {
            if (in_array($name, $excluded, true)) {
                continue;
            }

            $emDefinition = $container->findDefinition($id);
            $newId = $id . '_wrapped';
            $configArg = $emDefinition->getArgument(1);

            $decoratorDef = new Definition(ResettableEntityManager::class, [
                '$configuration' => $configArg,
                '$decoratedName' => $name,
                '$doctrineRegistry' => new Reference('doctrine'),
                '$wrapped' => new Reference($newId),
            ]);
            $decoratorDef->setPublic(true);

            $entityManagers[$name] = $newId;
            $resettableEntityManagers[$name] = new Reference($id);
            $container->setDefinition($id, $decoratorDef);
            $container->setDefinition($newId, $emDefinition);
        }

        $container->setParameter('doctrine.entity_managers', $entityManagers);
    }
}
