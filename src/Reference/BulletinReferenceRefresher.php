<?php

declare(strict_types=1);

namespace Pixel\TownHallBundle\Reference;

use Pixel\TownHallBundle\Entity\Bulletin;
use Pixel\TownHallBundle\Repository\BulletinRepository;
use Sulu\Bundle\ReferenceBundle\Application\Refresh\ReferenceRefresherInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class BulletinReferenceRefresher implements ReferenceRefresherInterface
{
    public function __construct(
        private BulletinReferenceProvider $bulletinReferenceProvider,
        private BulletinRepository $bulletinRepository,
        private WebspaceManagerInterface $webspaceManager,
        private string $suluContext,
    ) {
    }

    public static function getResourceKey(): string
    {
        return Bulletin::RESOURCE_KEY;
    }

    public function refresh(): \Generator
    {
        $locales = $this->webspaceManager->getAllLocales();
        $bulletins = $this->bulletinRepository->findAll();

        foreach ($bulletins as $bulletin) {
            foreach ($locales as $locale) {
                $this->bulletinReferenceProvider->updateReferences($bulletin, $locale, $this->suluContext);
            }

            yield $bulletin;
        }
    }
}
