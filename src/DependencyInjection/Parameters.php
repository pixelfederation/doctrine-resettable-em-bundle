<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DependencyInjection;

interface Parameters
{
    public const EXCLUDED_FROM_RESETTING = 'pixelfederation_doctrine_resettable_em_bundle.excluded_from_resetting';

    public const PING_INTERVAL = 'ping_interval';
}
