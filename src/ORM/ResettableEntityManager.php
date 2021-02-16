<?php
declare(strict_types=1);
/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\ORM;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Repository\RepositoryFactory;
use Exception;
use UnexpectedValueException;

class ResettableEntityManager extends EntityManagerDecorator
{
    private RepositoryFactory $repositoryFactory;

    private ManagerRegistry $doctrineRegistry;

    private string $decoratedName;

    public function __construct(
        Configuration $configuration,
        EntityManagerInterface $wrapped,
        ManagerRegistry $doctrineRegistry,
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
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function getRepository($className)
    {
        /** @psalm-suppress MixedReturnTypeCoercion */
        return $this->repositoryFactory->getRepository($this, $className);
    }

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
