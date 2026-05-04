<?php

declare(strict_types=1);

namespace Pixel\TownHallBundle\Reference;

use Pixel\TownHallBundle\Entity\Report;
use Pixel\TownHallBundle\Repository\ReportRepository;
use Sulu\Bundle\ReferenceBundle\Application\Refresh\ReferenceRefresherInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class ReportReferenceRefresher implements ReferenceRefresherInterface
{
    public function __construct(
        private ReportReferenceProvider $reportReferenceProvider,
        private ReportRepository $reportRepository,
        private WebspaceManagerInterface $webspaceManager,
        private string $suluContext,
    ) {
    }

    public static function getResourceKey(): string
    {
        return Report::RESOURCE_KEY;
    }

    public function refresh(): \Generator
    {
        $locales = $this->webspaceManager->getAllLocales();
        $reports = $this->reportRepository->findAll();

        foreach ($reports as $report) {
            foreach ($locales as $locale) {
                $this->reportReferenceProvider->updateReferences($report, $locale, $this->suluContext);
            }

            yield $report;
        }
    }
}
