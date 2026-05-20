<?php

declare(strict_types=1);

namespace Pixel\TownHallBundle\Reference;

use Pixel\TownHallBundle\Entity\Procedure;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollector;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;

class ProcedureReferenceProvider
{
    public function __construct(
        private ReferenceRepositoryInterface $referenceRepository,
    ) {
    }

    public function updateReferences(Procedure $procedure, string $locale, string $context): void
    {
        $referenceCollector = new ReferenceCollector(
            $this->referenceRepository,
            Procedure::RESOURCE_KEY,
            (string) $procedure->getId(),
            $locale,
            mb_substr($procedure->getTitle() ?? '', 0, 255),
            $context,
            ['id' => $procedure->getId(), 'locale' => $locale],
        );

        if ($cover = $procedure->getCover()) {
            $referenceCollector->addReference(
                MediaInterface::RESOURCE_KEY,
                (string) $cover->getId(),
                'cover',
            );
        }

        if ($document = $procedure->getDocument()) {
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
