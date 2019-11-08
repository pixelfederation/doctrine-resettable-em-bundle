<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\ORM;

use Doctrine\Bundle\DoctrineBundle\Registry;

use PixelFederation\DoctrineResettableEmBundle\ORM\EntityManagersHandler;
use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\ORM\ResettableEntityManager;
use Prophecy\Prophecy\ObjectProphecy;

class EntityManagersHandlerTest extends TestCase
{
    /**
     * @var EntityManagersHandler
     */
    private $emHandler;

    /**
     * @var ResettableEntityManager|ObjectProphecy
     */
    private $entityManagerProphecy;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->entityManagerProphecy = $this->prophesize(ResettableEntityManager::class);
        $this->doctrineRegistryProphecy = $this->prophesize(Registry::class);
        $this->emHandler = new EntityManagersHandler([$this->entityManagerProphecy->reveal()]);
    }

    /**
     *
     */
    public function testHandleEntityManagerClearingOnAppTerminate(): void
    {
        $this->entityManagerProphecy->clearOrResetIfNeeded()->shouldBeCalled();
        $this->emHandler->terminate();
    }
}
