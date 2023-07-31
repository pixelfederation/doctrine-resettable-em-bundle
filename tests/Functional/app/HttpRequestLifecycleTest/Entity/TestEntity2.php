<?php
declare(strict_types=1);
namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\HttpRequestLifecycleTest\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'test2')]
class TestEntity2
{
    public function __construct(#[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $id)
    {
    }
}
