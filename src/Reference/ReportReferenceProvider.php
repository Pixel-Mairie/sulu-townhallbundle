<?php

declare(strict_types=1);

namespace Pixel\TownHallBundle\Reference;

use Pixel\TownHallBundle\Entity\Report;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollector;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;

class ReportReferenceProvider
{
    public function __construct(
        private ReferenceRepositoryInterface $referenceRepository,
    ) {
    }

    public function updateReferences(Report $report, string $locale, string $context): void
    {
        $referenceCollector = new ReferenceCollector(
            $this->referenceRepository,
            Report::RESOURCE_KEY,
            (string) $report->getId(),
            $locale,
            $report->getTitle() ?? '',
            $context,
            ['id' => $report->getId()],
        );

        if ($document = $report->getDocument()) {
            $referenceCollector->addReference(
                MediaInterface::RESOURCE_KEY,
                (string) $document->getId(),
                'document',
            );
        }

        $referenceCollector->persistReferences();
        $this->referenceRepository->flush();
    }
}
