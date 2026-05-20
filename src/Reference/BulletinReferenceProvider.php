<?php

declare(strict_types=1);

namespace Pixel\TownHallBundle\Reference;

use Pixel\TownHallBundle\Entity\Bulletin;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollector;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;

class BulletinReferenceProvider
{
    public function __construct(
        private ReferenceRepositoryInterface $referenceRepository,
    ) {
    }

    public function updateReferences(Bulletin $bulletin, string $locale, string $context): void
    {
        $referenceCollector = new ReferenceCollector(
            $this->referenceRepository,
            Bulletin::RESOURCE_KEY,
            (string) $bulletin->getId(),
            $locale,
            mb_substr((string) $bulletin->getTitle(), 0, 191),
            $context,
            ['id' => $bulletin->getId()],
        );

        if ($cover = $bulletin->getCover()) {
            $referenceCollector->addReference(
                MediaInterface::RESOURCE_KEY,
                (string) $cover->getId(),
                'cover',
            );
        }

        if ($document = $bulletin->getDocument()) {
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
