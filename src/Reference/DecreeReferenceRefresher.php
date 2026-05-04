<?php

declare(strict_types=1);

namespace Pixel\TownHallBundle\Reference;

use Pixel\TownHallBundle\Entity\Decree;
use Pixel\TownHallBundle\Repository\DecreeRepository;
use Sulu\Bundle\ReferenceBundle\Application\Refresh\ReferenceRefresherInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class DecreeReferenceRefresher implements ReferenceRefresherInterface
{
    public function __construct(
        private DecreeReferenceProvider $decreeReferenceProvider,
        private DecreeRepository $decreeRepository,
        private WebspaceManagerInterface $webspaceManager,
        private string $suluContext,
    ) {
    }

    public static function getResourceKey(): string
    {
        return Decree::RESOURCE_KEY;
    }

    public function refresh(): \Generator
    {
        $locales = $this->webspaceManager->getAllLocales();
        $decrees = $this->decreeRepository->findAll();

        foreach ($decrees as $decree) {
            foreach ($locales as $locale) {
                $this->decreeReferenceProvider->updateReferences($decree, $locale, $this->suluContext);
            }

            yield $decree;
        }
    }
}
