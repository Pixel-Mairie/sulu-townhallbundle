<?php

namespace Pixel\TownHallBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;

/**
 * @ORM\Entity()
 * @ORM\Table(name="townhall_flash_info")
 * @ORM\Entity(repositoryClass="Pixel\TownHallBundle\Repository\FlashInfoRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class FlashInfo
{
    public const RESOURCE_KEY = 'flash_infos';

    public const FORM_KEY = 'flash_info_details';

    public const LIST_KEY = 'flash_infos';

    public const SECURITY_CONTEXT = 'townhall.flash_infos';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\Expose()
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity=MediaInterface::class)
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @Serializer\Expose()
     */
    private MediaInterface $cover;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @Serializer\Expose()
     * @var array<mixed>
     */
    private ?array $pdfs = null;

    /**
     * @var Collection<string, FlashInfoTranslation>
     * @ORM\OneToMany(targetEntity="Pixel\TownHallBundle\Entity\FlashInfoTranslation", mappedBy="flashInfo", cascade={"ALL"}, indexBy="locale")
     * @Serializer\Exclude
     */
    private $translations;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $defaultLocale;

    private string $locale = 'fr';

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCover(): MediaInterface
    {
        return $this->cover;
    }

    public function setCover(MediaInterface $cover): void
    {
        $this->cover = $cover;
    }

    /**
     * @return array<mixed>|null
     */
    public function getPdfs(): ?array
    {
        return $this->pdfs;
    }

    /**
     * @param array<mixed>|null $pdfs
     */
    public function setPdfs(?array $pdfs): void
    {
        $this->pdfs = $pdfs;
    }

    protected function getTranslation(string $locale = 'fr'): ?FlashInfoTranslation
    {
        if (! $this->translations->containsKey($locale)) {
            return null;
        }
        return $this->translations->get($locale);
    }

    protected function createTranslation(string $locale): FlashInfoTranslation
    {
        $translation = new FlashInfoTranslation($this, $locale);
        $this->translations->set($locale, $translation);
        return $translation;
    }

    /**
     * @return array<string, FlashInfoTranslation>
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

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @Serializer\VirtualProperty(name="title")
     * @return string
     */
    public function getTitle(): ?string
    {
        $translation = $this->getTranslation($this->locale);
        if (! $translation) {
            return null;
        }
        return $translation->getTitle();
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

    /**
     * @Serializer\VirtualProperty(name="description")
     */
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

    /**
     * @Serializer\VirtualProperty(name="is_active")
     */
    public function getIsActive(): ?bool
    {
        $translation = $this->getTranslation($this->locale);
        if (! $translation) {
            return null;
        }
        return $translation->getIsActive();
    }

    public function setIsActive(?bool $isActive): self
    {
        $translation = $this->getTranslation($this->locale);
        if (! $translation) {
            $translation = $this->createTranslation($this->locale);
        }
        $translation->setIsActive($isActive);
        return $this;
    }

    /**
     * @Serializer\VirtualProperty(name="published_at")
     */
    public function getPublishedAt(): ?\DateTimeImmutable
    {
        $translation = $this->getTranslation($this->locale);
        if (! $translation) {
            return null;
        }
        return $translation->getPublishedAt();
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): self
    {
        $translation = $this->getTranslation($this->locale);
        if ($translation) {
            $translation = $this->createTranslation($this->locale);
        }
        $translation->setPublishedAt($publishedAt);
        return $this;
    }
}
