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

    /**
     * Arrêtés actifs en vigueur sur au moins une partie de la période donnée : un arrêté sans date de fin
     * n'apparaît que sur sa date de début, un arrêté avec date de fin sur tout son intervalle.
     * Sans période, tous les arrêtés actifs sont retournés.
     *
     * @return Decree[]
     */
    public function findActiveByPeriod(?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null, ?int $categoryId = null): array
    {
        $queryBuilder = $this->createQueryBuilder("decree")
            ->addSelect("category")
            ->leftJoin("decree.category", "category")
            ->addSelect("pdf")
            ->leftJoin("decree.pdf", "pdf")
            ->where("decree.isActive = :isActive")
            ->setParameter("isActive", true)
            ->orderBy("decree.startDate", "DESC")
            ->addOrderBy("decree.title", "ASC");

        if ($from) {
            $queryBuilder
                ->andWhere("COALESCE(decree.endDate, decree.startDate) >= :from")
                ->setParameter("from", $from);
        }

        if ($to) {
            $queryBuilder
                ->andWhere("decree.startDate <= :to")
                ->setParameter("to", $to);
        }

        if ($categoryId) {
            $queryBuilder
                ->andWhere("category.id = :categoryId")
                ->setParameter("categoryId", $categoryId);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Années de début la plus ancienne et la plus récente parmi les arrêtés actifs.
     *
     * @return array{min: int, max: int}|null
     */
    public function findStartYearBounds(): ?array
    {
        $bounds = $this->createQueryBuilder("decree")
            ->select("MIN(decree.startDate) AS minDate", "MAX(decree.startDate) AS maxDate")
            ->where("decree.isActive = :isActive")
            ->setParameter("isActive", true)
            ->getQuery()
            ->getSingleResult();

        if (! $bounds['minDate']) {
            return null;
        }

        return [
            'min' => (int) substr((string) $bounds['minDate'], 0, 4),
            'max' => (int) substr((string) $bounds['maxDate'], 0, 4),
        ];
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
