<?php

declare(strict_types=1);

namespace Pixel\TownHallBundle\Content;

use JMS\Serializer\Annotation as Serializer;
use Pixel\TownHallBundle\Entity\FlashInfo;
use Sulu\Component\SmartContent\ItemInterface;

/**
 * @Serializer\ExclusionPolicy("all")
 */
class FlashInfoDataItem implements Iteminterface
{
    private FlashInfo $entity;

    public function __construct(FlashInfo $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @Serializer\VirtualProperty
     */
    public function getId(): string
    {
        return (string) $this->entity->getId();
    }

    /**
     * @Serializer\VirtualProperty
     */
    public function getTitle(): string
    {
        return (string) $this->entity->getTitle();
    }

    /**
     * @Serializer\VirtualProperty
     */
    public function getImage(): ?string
    {
        return null;
    }

    public function getResource(): FlashInfo
    {
        return $this->entity;
    }
}
