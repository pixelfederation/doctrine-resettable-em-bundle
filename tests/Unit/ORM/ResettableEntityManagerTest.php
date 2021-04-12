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
        /* @var $repositoryFactoryMock RepositoryFactory|ObjectProphecy */
        $repositoryFactoryMock = $this->prophesize(RepositoryFactory::class);
        $repositoryFactoryMock->getRepository(Argument::type(ResettableEntityManager::class), Argument::cetera())
            ->shouldBeCalledTimes(1);
        /* @var $configurationMock Configuration|ObjectProphecy */
        $configurationMock = $this->prophesize(Configuration::class);
        $configurationMock->getRepositoryFactory()
            ->shouldBeCalledTimes(1)
            ->willReturn($repositoryFactoryMock->reveal());
        /* @var $emMock EntityManagerInterface */
        $emMock = $this->prophesize(EntityManagerInterface::class)->reveal();
        /* @var $registryMock RegistryInterface */
        $em = new ResettableEntityManager(
            $configurationMock->reveal(),
            $emMock,
        );
        /* @var $repository EntityRepository */
        $em->getRepository(TestEntity::class);
    }
}
