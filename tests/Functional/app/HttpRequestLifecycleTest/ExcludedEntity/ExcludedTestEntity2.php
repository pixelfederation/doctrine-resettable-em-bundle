<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\HttpRequestLifecycleTest\ExcludedEntity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @final
 * @ORM\Entity
 * @ORM\Table(name="excluded_test2")
 */
#[ORM\Entity]
#[ORM\Table(name: 'excluded_test2')]
// phpcs:ignore SlevomatCodingStandard.Classes.RequireAbstractOrFinal.ClassNeitherAbstractNorFinal
class ExcludedTestEntity2
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
        private int $id,
    ) {
    }
}
