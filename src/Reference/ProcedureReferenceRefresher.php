<?php

declare(strict_types=1);

namespace Pixel\TownHallBundle\Reference;

use Pixel\TownHallBundle\Entity\Procedure;
use Pixel\TownHallBundle\Repository\ProcedureRepository;
use Sulu\Bundle\ReferenceBundle\Application\Refresh\ReferenceRefresherInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class ProcedureReferenceRefresher implements ReferenceRefresherInterface
{
    public function __construct(
        private ProcedureReferenceProvider $procedureReferenceProvider,
        private ProcedureRepository $procedureRepository,
        private WebspaceManagerInterface $webspaceManager,
        private string $suluContext,
    ) {
    }

    public static function getResourceKey(): string
    {
        return Procedure::RESOURCE_KEY;
    }

    public function refresh(): \Generator
    {
        $locales = $this->webspaceManager->getAllLocales();
        $procedures = $this->procedureRepository->findAll();

        foreach ($procedures as $procedure) {
            foreach ($locales as $locale) {
                $procedure->setLocale($locale);
                $this->procedureReferenceProvider->updateReferences($procedure, $locale, $this->suluContext);
            }

            yield $procedure;
        }
    }
}
