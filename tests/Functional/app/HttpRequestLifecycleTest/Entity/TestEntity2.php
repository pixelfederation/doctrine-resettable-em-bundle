<?php

declare(strict_types=1);

/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\HttpRequestLifecycleTest\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="test2")
 */
class TestEntity2
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }
}
