<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\ORM;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Repository\RepositoryFactory;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use PixelFederation\DoctrineResettableEmBundle\ORM\ResettableEntityManager;
use PHPUnit\Framework\TestCase;
use PixelFederation\DoctrineResettableEmBundle\Tests\Functional\app\HttpRequestLifecycleTest\Entity\TestEntity;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Doctrine\Persistence\ManagerRegistry as RegistryInterface;

class ResettableEntityManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @throws Exception
     */
    public function testGetRepository(): void
    {
        /* @var $repositoryMock ObjectRepository|ObjectProphecy */
        $repositoryMock = $this->prophesize(ObjectRepository::class);
        /* @var $repositoryFactoryMock RepositoryFactory|ObjectProphecy */
        $repositoryFactoryMock = $this->prophesize(RepositoryFactory::class);
        $repositoryFactoryMock->getRepository(Argument::type(ResettableEntityManager::class), Argument::cetera())
            ->shouldBeCalledTimes(1)
            ->willReturn($repositoryMock->reveal());
        /* @var $configurationMock Configuration|ObjectProphecy */
        $configurationMock = $this->prophesize(Configuration::class);
        $configurationMock->getRepositoryFactory()
            ->shouldBeCalledTimes(1)
            ->willReturn($repositoryFactoryMock->reveal());
        /* @var $emMock EntityManagerInterface */
        $emMock = $this->prophesize(EntityManagerInterface::class)->reveal();
        /* @var $registryMock RegistryInterface */
        $registryMock = $this->prophesize(RegistryInterface::class)->reveal();

        $em = new ResettableEntityManager(
            $configurationMock->reveal(),
            $emMock,
            $registryMock,
            'default'
        );

        $em->getRepository(TestEntity::class);
    }

    public function testClearOrResetIfNeededShouldClearWhenWrappedIsOpen(): void
    {
        /* @var $configurationMock Configuration|ObjectProphecy */
        $configurationMock = $this->prophesize(Configuration::class);
        $configurationMock->getRepositoryFactory()->willReturn($this->prophesize(RepositoryFactory::class));
        /* @var $emMock EntityManagerInterface */
        $emMock = $this->prophesize(EntityManagerInterface::class);
        $emMock->isOpen()->willReturn(true);
        $emMock->clear(Argument::is(null))->shouldBeCalled();
        /* @var $registryMock RegistryInterface */
        $registryMock = $this->prophesize(RegistryInterface::class)->reveal();

        $em = new ResettableEntityManager(
            $configurationMock->reveal(),
            $emMock->reveal(),
            $registryMock,
            'default'
        );

        $em->clearOrResetIfNeeded();
    }

    public function testClearOrResetIfNeededShouldResetWhenWrappedIsClosed(): void
    {
        $decoratedName = 'default';
        /* @var $configurationMock Configuration|ObjectProphecy */
        $configurationMock = $this->prophesize(Configuration::class);
        $configurationMock->getRepositoryFactory()->willReturn($this->prophesize(RepositoryFactory::class));
        /* @var $emMock EntityManagerInterface */
        $emMock = $this->prophesize(EntityManagerInterface::class);
        $emMock->isOpen()->willReturn(false);
        $emMock = $emMock->reveal();
        /* @var $registryMock RegistryInterface */
        $registryMock = $this->prophesize(RegistryInterface::class);
        $registryMock->resetManager(Argument::is($decoratedName))
            ->shouldBeCalled()->willReturn($this->createMock(ResettableEntityManager::class));
        $registryMock = $registryMock->reveal();

        $em = new ResettableEntityManager(
            $configurationMock->reveal(),
            $emMock,
            $registryMock,
            $decoratedName
        );

        $em->clearOrResetIfNeeded();
    }
}
