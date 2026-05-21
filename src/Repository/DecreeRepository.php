<?php

namespace Pixel\TownHallBundle\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Pixel\TownHallBundle\Entity\Decree;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryTrait;

class DecreeRepository extends EntityRepository implements DataProviderRepositoryInterface
{
    use DataProviderRepositoryTrait;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, new ClassMetadata(Decree::class));
    }

    protected function append(QueryBuilder $queryBuilder, $alias, $locale, $options = []): array
    {
        $queryBuilder
            ->andWhere($alias . '.isActive = :isActive')
            ->andWhere($queryBuilder->expr()->orX(
                $alias . '.endDate IS NULL',
                $alias . '.endDate >= :now'
            ));

        return [
            'isActive' => true,
            'now' => new \DateTimeImmutable('today'),
        ];
    }

    /**
     * @param string $alias
     * @param string $locale
     */
    public function appendJoins(QueryBuilder $queryBuilder, $alias, $locale): void
    {
        $queryBuilder->addSelect('category')->leftJoin($alias . '.category', 'category');
        $queryBuilder->addSelect('pdf')->leftJoin($alias . '.pdf', 'pdf');
    }

    /**
     * @param string $alias
     * @return string
     */
    public function appendCategoriesRelation(QueryBuilder $queryBuilder, $alias)
    {
        return $alias . '.category';
        //$queryBuilder->addSelect($alias.'.category');
    }
}
