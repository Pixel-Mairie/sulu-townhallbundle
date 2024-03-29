<?php

namespace Pixel\TownHallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;

/**
 * @ORM\Entity()
 * @ORM\Table(name="townall_setting")
 * @Serializer\ExclusionPolicy("all")
 */
class Setting implements AuditableInterface
{
    use AuditableTrait;

    public const RESOURCE_KEY = 'townhall_settings';

    public const FORM_KEY = 'townhall_settings';

    public const SECURITY_CONTEXT = 'townhall_settings.settings';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @Serializer\Expose()
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Serializer\Expose()
     */
    private ?string $townhallName = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Expose()
     */
    private ?string $meteo = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTownhallName(): ?string
    {
        return $this->townhallName;
    }

    public function setTownhallName(?string $townhallName): void
    {
        $this->townhallName = $townhallName;
    }

    public function getMeteo(): ?string
    {
        return $this->meteo;
    }

    public function setMeteo(?string $meteo): void
    {
        $this->meteo = $meteo;
    }
}
