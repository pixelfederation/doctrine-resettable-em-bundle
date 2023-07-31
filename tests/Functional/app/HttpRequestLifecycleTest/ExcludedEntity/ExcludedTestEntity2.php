<?php
declare(strict_types=1);
namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\HttpRequestLifecycleTest\ExcludedEntity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="excluded_test2")
 */
#[ORM\Entity]
#[ORM\Table(name: 'excluded_test2')]
class ExcludedTestEntity2
{
    public function __construct(#[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $id)
    {
    }
}
