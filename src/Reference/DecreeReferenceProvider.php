<?php

declare(strict_types=1);

namespace Pixel\TownHallBundle\Reference;

use Pixel\TownHallBundle\Entity\Decree;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollector;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;

class DecreeReferenceProvider
{
    public function __construct(
        private ReferenceRepositoryInterface $referenceRepository,
    ) {
    }

    public function updateReferences(Decree $decree, string $locale, string $context): void
    {
        $referenceCollector = new ReferenceCollector(
            $this->referenceRepository,
            Decree::RESOURCE_KEY,
            (string) $decree->getId(),
            $locale,
            mb_substr((string) $decree->getTitle(), 0, 255),
            $context,
            ['id' => $decree->getId()],
        );

        if ($pdf = $decree->getPdf()) {
            $referenceCollector->addReference(
                MediaInterface::RESOURCE_KEY,
                (string) $pdf->getId(),
                'pdf',
            );
        }

        $referenceCollector->persistReferences();
        $this->referenceRepository->flush();
    }
}
