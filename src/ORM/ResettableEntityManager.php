<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\ORM;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Repository\RepositoryFactory;
use Doctrine\Persistence\ManagerRegistry;
use Override;
use UnexpectedValueException;

/**
 * @final
 */
// phpcs:ignore SlevomatCodingStandard.Classes.RequireAbstractOrFinal.ClassNeitherAbstractNorFinal
class ResettableEntityManager extends EntityManagerDecorator
{
    private readonly RepositoryFactory $repositoryFactory;

    public function __construct(
        Configuration $configuration,
        EntityManagerInterface $wrapped,
        private readonly ManagerRegistry $doctrineRegistry,
        private readonly string $decoratedName,
    ) {
        $this->repositoryFactory = $configuration->getRepositoryFactory();

        parent::__construct($wrapped);
    }

    /**
     * @inheritDoc
     * @template T of object
     * @param class-string<T> $className
     * @return EntityRepository<T>
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    #[Override]
    public function getRepository(string $className): EntityRepository
    {
        return $this->repositoryFactory->getRepository($this, $className);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function createQuery($dql = ''): Query
    {
        $query = new Query($this);
        if (trim($dql) !== '') {
            $query->setDQL($dql);
        }

        return $query;
    }

    #[Override]
    public function createNativeQuery(string $sql, ResultSetMapping $rsm): NativeQuery
    {
        $query = new NativeQuery($this);

        $query->setSQL($sql);
        $query->setResultSetMapping($rsm);

        return $query;
    }

    #[Override]
    public function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this);
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
                sprintf('Invalid entity manager class - %s', $newEntityManager::class),
            );
        }
    }
}
