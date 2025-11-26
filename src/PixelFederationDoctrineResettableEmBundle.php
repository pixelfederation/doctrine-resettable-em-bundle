<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle;

use Override;
use PixelFederation\DoctrineResettableEmBundle\DependencyInjection\CompilerPass\AliveKeeperPass;
use PixelFederation\DoctrineResettableEmBundle\DependencyInjection\CompilerPass\EntityManagerDecoratorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class PixelFederationDoctrineResettableEmBundle extends Bundle
{
    #[Override]
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new EntityManagerDecoratorPass());
        $container->addCompilerPass(new AliveKeeperPass());
    }
}
