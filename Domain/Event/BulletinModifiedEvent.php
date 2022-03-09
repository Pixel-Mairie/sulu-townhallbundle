<?php

declare(strict_types=1);

namespace Pixel\TownHallBundle\Domain\Event;

use Pixel\TownHallBundle\Entity\Bulletin;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;

class BulletinModifiedEvent extends DomainEvent
{
    private Bulletin $bulletin;
    private array $payload;

    public function __construct(Bulletin $bulletin, array $payload)
    {
        parent::__construct();
        $this->bulletin = $bulletin;
        $this->payload = $payload;
    }

    public function getBulletin(): Bulletin
    {
        return $this->bulletin;
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
    }

    public function getEventType(): string
    {
        return 'modified';
    }

    public function getResourceKey(): string
    {
        return Bulletin::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string)$this->bulletin->getId();
    }

    public function getResourceTitle(): ?string
    {
        return $this->bulletin->getTitle();
    }

    public function getResourceSecurityContext(): ?string
    {
        return Bulletin::SECURITY_CONTEXT;
    }
}
