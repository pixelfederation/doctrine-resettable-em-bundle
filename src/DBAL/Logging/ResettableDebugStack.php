<?php

declare(strict_types=1);

/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Logging;

use Doctrine\DBAL\Logging\DebugStack;
use Symfony\Contracts\Service\ResetInterface;

class ResettableDebugStack extends DebugStack implements ResetInterface
{
    public function reset(): void
    {
        $this->queries = [];
        $this->currentQuery = 0;
        $this->start = null;
    }
}
