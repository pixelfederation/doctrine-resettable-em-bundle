<?php // phpcs:ignore PSR1.Files.SideEffects.FoundWithSymbols

declare(strict_types=1);

namespace PixelFederation\DoctrineResettableEmBundle\ORM;

use Composer\InstalledVersions;
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
use Doctrine\Persistence\ObjectRepository;
use Exception;
use UnexpectedValueException;

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
if (version_compare(InstalledVersions::getVersion('doctrine/orm'), '3.0.0', '<')) {

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
         * @template T as object
         * @param class-string<T> $className
         * @return ObjectRepository<T>
         * @throws Exception
         * @psalm-suppress LessSpecificImplementedReturnType
         * @psalm-suppress MoreSpecificImplementedParamType
         * @psalm-suppress MixedReturnTypeCoercion
         */
        public function getRepository($className): ObjectRepository
        {
            /** @psalm-suppress MixedReturnTypeCoercion */
            return $this->repositoryFactory->getRepository($this, $className);
        }

        /**
         * {@inheritDoc}
         */
        public function createQuery($dql = ''): Query
        {
            $query = new Query($this);

            if (! empty($dql)) {
                $query->setDQL($dql);
            }

            return $query;
        }

        /**
         * {@inheritDoc}
         *
         * @phpstan-ignore-next-line
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
                    sprintf('Invalid entity manager class - %s', $newEntityManager::class)
                );
            }
        }
    }

    return;
}

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
     * @template T as object
     * @param class-string<T> $className
     * @return EntityRepository<T>
     * @throws Exception
     * @psalm-suppress LessSpecificImplementedReturnType
     * @psalm-suppress MoreSpecificImplementedParamType
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function getRepository($className): EntityRepository
    {
        /** @psalm-suppress MixedReturnTypeCoercion */
        return $this->repositoryFactory->getRepository($this, $className);
    }

    /**
     * {@inheritDoc}
     */
    public function createQuery($dql = ''): Query
    {
        $query = new Query($this);

        if (! empty($dql)) {
            $query->setDQL($dql);
        }

        return $query;
    }

    public function createNativeQuery(string $sql, ResultSetMapping $rsm): NativeQuery
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
                sprintf('Invalid entity manager class - %s', $newEntityManager::class)
            );
        }
    }
}
