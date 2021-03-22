<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle;

use PixelFederation\DoctrineResettableEmBundle\DependencyInjection\CompilerPass\AliveKeeperPass;
use PixelFederation\DoctrineResettableEmBundle\DependencyInjection\CompilerPass\EntityManagerDecoratorPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class PixelFederationDoctrineResettableEmBundle extends Bundle
{
    /**
     * @inheritdoc
     */
    public function build(ContainerBuilder $container): void
    {
        // this compiler pass needs to run before ResettableServicePass because it adds the kernel.reset tag
        // to resettable entity managers
        $container->addCompilerPass(new EntityManagerDecoratorPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1);
        $container->addCompilerPass(new AliveKeeperPass());
    }
}
