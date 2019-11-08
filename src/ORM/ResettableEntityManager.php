<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\ORM;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Repository\RepositoryFactory;
use Exception;
use Symfony\Bridge\Doctrine\RegistryInterface;
use UnexpectedValueException;

/**
 *
 */
class ResettableEntityManager extends EntityManagerDecorator
{
    /**
     * @var RepositoryFactory
     */
    private $repositoryFactory;

    /**
     * @var RegistryInterface
     */
    private $doctrineRegistry;

    /**
     * @var string
     */
    private $decoratedName;

    /**
     * @param Configuration          $configuration
     * @param EntityManagerInterface $wrapped
     * @param RegistryInterface      $doctrineRegistry
     * @param string                 $decoratedName
     */
    public function __construct(
        Configuration $configuration,
        EntityManagerInterface $wrapped,
        RegistryInterface $doctrineRegistry,
        string $decoratedName
    ) {
        $this->repositoryFactory = $configuration->getRepositoryFactory();
        $this->doctrineRegistry = $doctrineRegistry;
        $this->decoratedName = $decoratedName;
        parent::__construct($wrapped);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getRepository($className)
    {
        return $this->repositoryFactory->getRepository($this, $className);
    }

    /**
     * @return void
     */
    public function clearOrResetIfNeeded(): void
    {
        if ($this->wrapped->isOpen()) {
            $this->clear();

            return;
        }

        $newEntityManager = $this->doctrineRegistry->resetManager($this->decoratedName);

        if (!$newEntityManager instanceof EntityManagerInterface) {
            throw new UnexpectedValueException(
                sprintf('Invalid entity manager class - %s', get_class($newEntityManager))
            );
        }

        $this->wrapped = $newEntityManager;
    }
}
