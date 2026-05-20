<?php

namespace Pixel\TownHallBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;

#[ORM\Entity(repositoryClass: "Pixel\TownHallBundle\Repository\ProcedureRepository")]
#[ORM\Table(name: "townhall_procedure")]
#[Serializer\ExclusionPolicy("all")]
class Procedure
{
    public const RESOURCE_KEY = 'procedures';

    public const FORM_KEY = 'procedure_details';

    public const LIST_KEY = 'procedures';

    public const SECURITY_CONTEXT = 'townhall.procedures';

    #[ORM\Id()]
    #[ORM\GeneratedValue()]
    #[ORM\Column(type: "integer")]
    #[Serializer\Expose()]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MediaInterface::class)]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[Serializer\Expose()]
    private ?MediaInterface $cover = null;

    #[ORM\Column(type: "boolean", nullable: true)]
    #[Serializer\Expose()]
    private ?bool $state;

    #[ORM\Column(type: "string", nullable: true)]
    #[Serializer\Expose()]
    private ?string $externalLink = null;

    #[ORM\ManyToOne(targetEntity: MediaInterface::class)]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[Serializer\Expose()]
    private ?MediaInterface $document = null;

    #[ORM\ManyToOne(targetEntity: CategoryInterface::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Serializer\Expose()]
    private ?CategoryInterface $category = null;

    /**
     * @var Collection<string, ProcedureTranslation>
     */
    #[ORM\OneToMany(targetEntity: "Pixel\TownHallBundle\Entity\ProcedureTranslation", mappedBy: "procedure", cascade: ["ALL"], indexBy: "locale")]
    #[Serializer\Exclude]
    private $translations;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $defaultLocale;

    private string $locale = 'fr';

    public function __construct()
    {
        $this->state = true;
        $this->translations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    #[Serializer\VirtualProperty(name: "title")]
    public function getTitle(): ?string
    {
        $translation = $this->getTranslation($this->locale);
        if (! $translation) {
            return null;
        }
        return $translation->getTitle();
    }

    protected function getTranslation(string $locale = 'fr'): ?ProcedureTranslation
    {
        if (! $this->translations->containsKey($locale)) {
            return null;
        }
        return $this->translations->get($locale);
    }

    public function setTitle(string $title): self
    {
        $translation = $this->getTranslation($this->locale);
        if (! $translation) {
            $translation = $this->createTranslation($this->locale);
        }
        $translation->setTitle($title);
        return $this;
    }

    protected function createTranslation(string $locale): ProcedureTranslation
    {
        $translation = new ProcedureTranslation($this, $locale);
        $this->translations->set($locale, $translation);
        return $translation;
    }

    #[Serializer\VirtualProperty(name: "description")]
    public function getDescription(): ?string
    {
        $translation = $this->getTranslation($this->locale);
        if (! $translation) {
            return null;
        }
        return $translation->getDescription();
    }

    public function setDescription(string $description): self
    {
        $translation = $this->getTranslation($this->locale);
        if (! $translation) {
            $translation = $this->createTranslation($this->locale);
        }
        $translation->setDescription($description);
        return $this;
    }

    #[Serializer\VirtualProperty(name: "route")]
    public function getRoutePath(): ?string
    {
        $translation = $this->getTranslation($this->locale);
        if (! $translation) {
            return null;
        }
        return $translation->getRoutePath();
    }

    public function setRoutePath(string $routePath): self
    {
        $translation = $this->getTranslation($this->locale);
        if (! $translation) {
            $translation = $this->createTranslation($this->locale);
        }
        $translation->setRoutePath($routePath);
        return $this;
    }

    /**
     * @return array<mixed>|null
     */
    #[Serializer\VirtualProperty(name: "seo")]
    public function getSeo(): ?array
    {
        $translation = $this->getTranslation($this->locale);
        if (! $translation) {
            return null;
        }
        return $translation->getSeo();
    }

    /**
     * @return array<string, array<string>>
     */
    protected function emptySeo(): array
    {
        return [
            "seo" => [
                "title" => "",
                "description" => "",
                "keywords" => "",
                "canonicalUrl" => "",
                "noIndex" => "",
                "noFollow" => "",
                "hideinSitemap" => "",
            ],
        ];
    }

    /**
     * @return array<mixed>|null
     */
    #[Serializer\VirtualProperty(name: "ext")]
    public function getExt(): ?array
    {
        $translation = $this->getTranslation($this->locale);
        if (! $translation) {
            return null;
        }
        return ($translation->getSeo()) ? [
            'seo' => $translation->getSeo(),
        ] : $this->emptySeo();
    }

    /**
     * @param array<mixed>|null $seo
     */
    public function setSeo(?array $seo): self
    {
        $translation = $this->getTranslation($this->locale);
        if (! $translation) {
            $translation = $this->createTranslation($this->locale);
        }
        $translation->setSeo($seo);
        return $this;
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

    public function getExternalLink(): ?string
    {
        return $this->externalLink;
    }

    public function setExternalLink(?string $externalLink): void
    {
        $this->externalLink = $externalLink;
    }

    public function getDocument(): ?MediaInterface
    {
        return $this->document;
    }

    public function setDocument(?MediaInterface $document): void
    {
        $this->document = $document;
    }

    public function getCategory(): ?CategoryInterface
    {
        return $this->category;
    }

    public function setCategory(?CategoryInterface $category): void
    {
        $this->category = $category;
    }

    /**
     * @return array<string, ProcedureTranslation>
     */
    public function getTranslations(): array
    {
        return $this->translations->toArray();
    }

    public function getDefaultLocale(): ?string
    {
        return $this->defaultLocale;
    }

    public function setDefaultLocale(?string $defaultLocale): void
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }
}
