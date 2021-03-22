<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\DependencyInjection\CompilerPass;

use PixelFederation\DoctrineResettableEmBundle\ORM\ResettableEntityManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class EntityManagerDecoratorPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        $entityManagers = $container->getParameter('doctrine.entity_managers');
        $resettableEntityManagers = [];

        foreach ($entityManagers as $name => $id) {
            $emDefinition = $container->findDefinition($id);
            $newId = $id . '_wrapped';
            $configArg = $emDefinition->getArgument(1);

            $decoratorDef = new Definition(ResettableEntityManager::class, [
                '$configuration' => $configArg,
                '$wrapped' => new Reference($newId),
                '$doctrineRegistry' => new Reference('doctrine'),
                '$decoratedName' => $name,
            ]);
            $decoratorDef->setPublic(true);
            $decoratorDef->addTag('kernel.reset', ['method' => 'clearOrResetIfNeeded']);

            $entityManagers[$name] = $newId;
            $resettableEntityManagers[$name] = new Reference($id);
            $container->setDefinition($id, $decoratorDef);
            $container->setDefinition($newId, $emDefinition);
        }

        $container->setParameter('doctrine.entity_managers', $entityManagers);
    }
}
