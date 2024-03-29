<?php

declare(strict_types=1);

namespace Pixel\TownHallBundle\Content\Type;

use Doctrine\ORM\EntityManagerInterface;
use Pixel\TownHallBundle\Entity\Report;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\SimpleContentType;

class SingleReportSelection extends SimpleContentType
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct('single_report_selection', null);
    }

    public function getContentData(PropertyInterface $property): ?Report
    {
        $id = $property->getValue();

        if (empty($id)) {
            return null;
        }

        return $this->entityManager->getRepository(Report::class)->find($id);
    }

    /**
     * @return array<string, int|null>
     */
    public function getViewData(PropertyInterface $property): array
    {
        return [
            'id' => $property->getValue(),
        ];
    }
}
