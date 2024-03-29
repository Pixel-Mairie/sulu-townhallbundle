<?php

declare(strict_types=1);

namespace Pixel\TownHallBundle\Domain\Event;

use Pixel\TownHallBundle\Entity\PublicMarket;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;

class PublicMarketRestoredEvent extends DomainEvent
{
    private PublicMarket $publicMarket;

    /**
     * @var array<mixed>
     */
    private array $payload;

    /**
     * @param array<mixed> $payload
     */
    public function __construct(PublicMarket $publicMarket, array $payload)
    {
        parent::__construct();
        $this->publicMarket = $publicMarket;
        $this->payload = $payload;
    }

    public function getPublicMarket(): PublicMarket
    {
        return $this->publicMarket;
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
    }

    public function getEventType(): string
    {
        return 'restored';
    }

    public function getResourceKey(): string
    {
        return PublicMarket::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->publicMarket->getId();
    }

    public function getResourceTitle(): ?string
    {
        return $this->publicMarket->getTitle();
    }

    public function getResourceSecurityContext(): ?string
    {
        return PublicMarket::SECURITY_CONTEXT;
    }
}
