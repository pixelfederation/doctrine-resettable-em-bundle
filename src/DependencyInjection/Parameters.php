<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DependencyInjection;

interface Parameters
{
    public const EXCLUDED_FROM_PROCESSING_ENTITY_MANAGERS =
        'pixelfederation_doctrine_resettable_em_bundle.excluded_from_processing.entity_managers';

    public const EXCLUDED_FROM_PROCESSING_DBAL_CONNECTIONS =
        'pixelfederation_doctrine_resettable_em_bundle.excluded_from_processing.connections.dbal';

    public const EXCLUDED_FROM_PROCESSING_REDIS_CLUSTER_CONNECTIONS =
        'pixelfederation_doctrine_resettable_em_bundle.excluded_from_processing.connections.redis_cluster';

    public const PING_INTERVAL = 'pixelfederation_doctrine_resettable_em_bundle.ping_interval';

    public const CHECK_ACTIVE_TRANSACTIONS = 'pixelfederation_doctrine_resettable_em_bundle.check_active_transactions';
}
