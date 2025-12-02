<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\FailoverAware;

enum ConnectionType: string
{
    case WRITER = 'writer';
    case READER = 'reader';
}
