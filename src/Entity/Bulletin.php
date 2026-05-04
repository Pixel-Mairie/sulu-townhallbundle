<?php

namespace Pixel\TownHallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;

#[ORM\Entity()]
#[ORM\Table(name: "townhall_bulletin")]
#[Serializer\ExclusionPolicy("all")]
class Bulletin implements AuditableInterface
{
    use AuditableTrait;

    public const RESOURCE_KEY = 'bulletins';

    public const FORM_KEY = 'bulletin_details';

    public const LIST_KEY = 'bulletins';

    public const SECURITY_CONTEXT = 'townhall_bulletin.bulletins';

    #[ORM\Id()]
    #[ORM\GeneratedValue()]
    #[ORM\Column(type: "integer")]
    #[Serializer\Expose()]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    #[Serializer\Expose()]
    private ?string $title = null;

    #[ORM\Column(type: "text", nullable: true)]
    #[Serializer\Expose()]
    private ?string $description = null;

    #[ORM\Column(type: "date_immutable")]
    #[Serializer\Expose()]
    private ?\DateTimeImmutable $dateBulletin = null;

    #[ORM\ManyToOne(targetEntity: MediaInterface::class)]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[Serializer\Expose()]
    private ?MediaInterface $document = null;

    #[ORM\ManyToOne(targetEntity: MediaInterface::class)]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[Serializer\Expose()]
    private ?MediaInterface $cover = null;

    #[ORM\Column(type: "boolean", nullable: true)]
    #[Serializer\Expose()]
    private ?bool $state;

    public function __construct()
    {
        $this->state = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getDateBulletin(): ?\DateTimeImmutable
    {
        return $this->dateBulletin;
    }

    public function setDateBulletin(?\DateTimeImmutable $dateBulletin): void
    {
        $this->dateBulletin = $dateBulletin;
    }

    public function getDocument(): ?MediaInterface
    {
        return $this->document;
    }

    public function setDocument(?MediaInterface $document): void
    {
        $this->document = $document;
    }

    public function getCover(): ?MediaInterface
    {
        return $this->cover;
    }

    public function setCover(?MediaInterface $cover): void
    {
        $this->cover = $cover;
    }

    public function getState(): ?bool
    {
        return $this->state;
    }

    public function setState(?bool $state): void
    {
        $this->state = $state;
    }
}
