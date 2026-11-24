<?php

declare(strict_types=1);

namespace Pixel\TownHallBundle\Controller\Website;

use Pixel\TownHallBundle\Entity\Report;
use Pixel\TownHallBundle\Repository\ReportRepository;
use Sulu\Bundle\WebsiteBundle\Controller\DefaultController;
use Sulu\Component\Content\Compat\StructureInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Contrôleur de page listant les comptes-rendus des conseils municipaux, groupés par année, filtrables par année et
 * paginés. À référencer dans la balise <controller> du template de page. Le nombre de comptes-rendus par page peut
 * être surchargé par une propriété « limit » du template de page.
 */
class ReportController extends DefaultController
{
    public const ALL_YEARS = "all";

    public const DEFAULT_LIMIT = 20;

    private ReportRepository $reportRepository;

    private RequestStack $requestStack;

    public function __construct(
        #[Autowire(service: 'townhall.report_repository')]
        ReportRepository $reportRepository,
        RequestStack $requestStack
    ) {
        $this->reportRepository = $reportRepository;
        $this->requestStack = $requestStack;
    }

    protected function getAttributes($attributes, ?StructureInterface $structure = null, $preview = false)
    {
        $attributes = parent::getAttributes($attributes, $structure, $preview);

        $request = $this->requestStack->getCurrentRequest();
        $currentYear = (int) date("Y");

        $years = $this->getYears($currentYear);

        // Année : l'année courante par défaut, « all » pour toutes les années
        $year = $request ? (string) $request->query->get("year", (string) $currentYear) : (string) $currentYear;
        if (self::ALL_YEARS !== $year) {
            $year = in_array((int) $year, $years, true) ? (int) $year : $currentYear;
        }

        $from = self::ALL_YEARS === $year ? null : new \DateTimeImmutable($year . "-01-01 00:00:00");
        $to = self::ALL_YEARS === $year ? null : new \DateTimeImmutable($year . "-12-31 23:59:59");

        // Pagination en base : un groupe année peut se poursuivre sur la page suivante
        $limit = (int) ($attributes['content']['limit'] ?? 0) ?: self::DEFAULT_LIMIT;
        $total = $this->reportRepository->countActiveByPeriod($from, $to);
        $pages = max(1, (int) ceil($total / $limit));
        $page = $request ? max(1, (int) $request->query->get("page", "1")) : 1;
        $page = min($page, $pages);

        $reports = $this->reportRepository->findActiveByPeriod($from, $to, $limit, ($page - 1) * $limit);

        $attributes['reportYears'] = $years;
        $attributes['reportFilters'] = [
            'year' => $year,
        ];
        $attributes['reportsByYear'] = $this->groupByYear($reports);
        $attributes['reportPagination'] = [
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ];

        return $attributes;
    }

    /**
     * Années proposées dans le filtre, de la plus récente à la plus ancienne. L'année courante est toujours proposée.
     *
     * @return int[]
     */
    private function getYears(int $currentYear): array
    {
        $bounds = $this->reportRepository->findYearBounds();
        $min = $bounds ? min($bounds['min'], $currentYear) : $currentYear;
        $max = $bounds ? max($bounds['max'], $currentYear) : $currentYear;

        return range($max, $min);
    }

    /**
     * @param Report[] $reports triés par date décroissante
     *
     * @return array<int, Report[]>
     */
    private function groupByYear(array $reports): array
    {
        $grouped = [];
        foreach ($reports as $report) {
            $grouped[(int) $report->getDateReport()->format("Y")][] = $report;
        }

        return $grouped;
    }
}
