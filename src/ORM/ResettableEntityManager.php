<?php

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\ORM;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Repository\RepositoryFactory;
use Doctrine\Persistence\ManagerRegistry;
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
     * {@inheritDoc}
     *
     * @psalm-suppress LessSpecificImplementedReturnType
     * @psalm-suppress MoreSpecificImplementedParamType
     * @psalm-suppress MixedReturnTypeCoercion
     * @psalm-suppress MoreSpecificReturnType, LessSpecificReturnStatement
     */
    public function getRepository($className)
    {
        return $this->repositoryFactory->getRepository($this, $className); //@phpstan-ignore-line
    }

    /**
     * {@inheritDoc}
     */
    public function createQuery($dql = ''): Query
    {
        $query = new Query($this);

        if (! empty($dql)) { //phpcs:ignore SlevomatCodingStandard.ControlStructures.DisallowEmpty.DisallowedEmpty
            $query->setDQL($dql);
        }

        return $query;
    }

    /**
     * {@inheritDoc}
     */
    public function createNativeQuery($sql, ResultSetMapping $rsm): NativeQuery
    {
        $query = new NativeQuery($this);

        $query->setSQL($sql);
        $query->setResultSetMapping($rsm);

        return $query;
    }

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
