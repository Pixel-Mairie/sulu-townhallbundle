<?php

namespace Pixel\TownHallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;

#[ORM\Entity(repositoryClass: "Pixel\TownHallBundle\Repository\ProcedureRepository")]
#[ORM\Table(name: "townhall_procedure_translation")]
#[Serializer\ExclusionPolicy("all")]
class ProcedureTranslation implements AuditableInterface
{
    use AuditableTrait;

    #[ORM\Id()]
    #[ORM\GeneratedValue()]
    #[ORM\Column(type: "integer")]
    #[Serializer\Expose()]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: "Pixel\TownHallBundle\Entity\Procedure", inversedBy: "translations")]
    #[ORM\JoinColumn(nullable: true)]
    private Procedure $procedure;

    #[ORM\Column(type: "string", length: 5)]
    #[Serializer\Expose()]
    private string $locale;

    #[ORM\Column(type: "string", length: 255)]
    #[Serializer\Expose()]
    private string $title;

    #[ORM\Column(type: "text")]
    #[Serializer\Expose()]
    private string $description;

    #[ORM\Column(type: "string", length: 255)]
    #[Serializer\Expose()]
    private string $routePath;

    /**
     * @var array<mixed>|null
     */
    #[ORM\Column(type: "json", nullable: true)]
    #[Serializer\Expose()]
    private ?array $seo = null;

    public function __construct(Procedure $procedure, string $locale)
    {
        $this->procedure = $procedure;
        $this->locale = $locale;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProcedure(): Procedure
    {
        return $this->procedure;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getRoutePath(): string
    {
        return $this->routePath ?? '';
    }

    public function setRoutePath(string $routePath): void
    {
        $this->routePath = $routePath;
    }

    /**
     * @return array<mixed>|null
     */
    public function getSeo(): ?array
    {
        return $this->seo;
    }

    /**
     * @param array<mixed>|null $seo
     */
    public function setSeo(?array $seo): void
    {
        $this->seo = $seo;
    }
}
