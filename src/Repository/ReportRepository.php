<?php

namespace Pixel\TownHallBundle\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Pixel\TownHallBundle\Entity\Report;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryTrait;

class ReportRepository extends EntityRepository implements DataProviderRepositoryInterface
{
    use DataProviderRepositoryTrait;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, new ClassMetadata(Report::class));
    }

    /**
     * Comptes-rendus actifs dont la date est comprise dans la période donnée, du plus récent au plus ancien.
     * Sans période, tous les comptes-rendus actifs sont concernés.
     *
     * @return Report[]
     */
    public function findActiveByPeriod(?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null, int $limit = 0, int $offset = 0): array
    {
        $queryBuilder = $this->createActiveByPeriodQueryBuilder($from, $to)
            ->addSelect("document")
            ->leftJoin("report.document", "document")
            ->orderBy("report.dateReport", "DESC")
            ->addOrderBy("report.title", "ASC");

        if ($limit > 0) {
            $queryBuilder
                ->setFirstResult($offset)
                ->setMaxResults($limit);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function countActiveByPeriod(?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null): int
    {
        return (int) $this->createActiveByPeriodQueryBuilder($from, $to)
            ->select("COUNT(report.id)")
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Années la plus ancienne et la plus récente parmi les comptes-rendus actifs.
     *
     * @return array{min: int, max: int}|null
     */
    public function findYearBounds(): ?array
    {
        $bounds = $this->createQueryBuilder("report")
            ->select("MIN(report.dateReport) AS minDate", "MAX(report.dateReport) AS maxDate")
            ->where("report.isActive = :isActive")
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

    private function createActiveByPeriodQueryBuilder(?\DateTimeImmutable $from, ?\DateTimeImmutable $to): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder("report")
            ->where("report.isActive = :isActive")
            ->setParameter("isActive", true);

        if ($from) {
            $queryBuilder
                ->andWhere("report.dateReport >= :from")
                ->setParameter("from", $from);
        }

        if ($to) {
            $queryBuilder
                ->andWhere("report.dateReport <= :to")
                ->setParameter("to", $to);
        }

        return $queryBuilder;
    }

    protected function append(QueryBuilder $queryBuilder, $alias, $locale, $options = []): array
    {
        $queryBuilder->andWhere($alias . '.isActive = :isActive');

        return [
            'isActive' => true,
        ];
    }

    /**
     * @param string $alias
     * @param string $locale
     */
    public function appendJoins(QueryBuilder $queryBuilder, $alias, $locale): void
    {
        //$queryBuilder->addSelect('category')->leftJoin($alias . '.category', 'category');
        //$queryBuilder->addSelect($alias.'.category');
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
