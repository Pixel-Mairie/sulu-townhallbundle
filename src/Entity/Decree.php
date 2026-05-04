<?php

namespace Pixel\TownHallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;

#[ORM\Entity()]
#[ORM\Table(name: "townhall_decree")]
#[Serializer\ExclusionPolicy("all")]
class Decree implements AuditableInterface
{
    use AuditableTrait;

    public const RESOURCE_KEY = "decrees";

    public const FORM_KEY = "decree_details";

    public const LIST_KEY = "decrees";

    public const SECURITY_CONTEXT = "townhall_decrees.deccres";

    #[ORM\Id()]
    #[ORM\GeneratedValue()]
    #[ORM\Column(type: "integer")]
    #[Serializer\Expose()]
    private ?int $id = null;

    #[ORM\Column(type: "string")]
    #[Serializer\Expose()]
    private string $title;

    #[ORM\Column(type: "datetime_immutable")]
    #[Serializer\Expose()]
    private \DateTimeImmutable $startDate;

    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    #[Serializer\Expose()]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: "text", nullable: true)]
    #[Serializer\Expose()]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: MediaInterface::class)]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[Serializer\Expose()]
    private MediaInterface $pdf;

    #[ORM\Column(type: "boolean", nullable: true)]
    #[Serializer\Expose()]
    private ?bool $isActive = null;

    #[ORM\ManyToOne(targetEntity: CategoryInterface::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Serializer\Expose()]
    private CategoryInterface $category; //Correspond au type d'arrêté

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): void
    {
        $this->endDate = $endDate;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getPdf(): MediaInterface
    {
        return $this->pdf;
    }

    public function setPdf(MediaInterface $pdf): void
    {
        $this->pdf = $pdf;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getCategory(): CategoryInterface
    {
        return $this->category;
    }

    public function setCategory(CategoryInterface $category): void
    {
        $this->category = $category;
    }
}
