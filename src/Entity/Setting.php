<?php

namespace Pixel\TownHallBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;

#[ORM\Entity()]
#[ORM\Table(name: "townhall_setting")]
#[Serializer\ExclusionPolicy("all")]
class Setting implements AuditableInterface
{
    use AuditableTrait;

    public const RESOURCE_KEY = 'townhall_settings';

    public const FORM_KEY = 'townhall_settings';

    public const SECURITY_CONTEXT = 'townhall_settings.settings';

    #[ORM\Id()]
    #[ORM\GeneratedValue()]
    #[ORM\Column(type: "integer")]
    #[Serializer\Expose()]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Serializer\Expose()]
    private ?string $townhallName = null;

    #[ORM\Column(type: "text", nullable: true)]
    #[Serializer\Expose()]
    private ?string $meteo = null;

    #[ORM\Column(type: "string", nullable: true, length: 255)]
    #[Serializer\Expose()]
    private ?string $email;

    #[ORM\Column(type: "string", nullable: true, length: 255)]
    #[Serializer\Expose()]
    private ?string $phone;

    #[ORM\Column(type: "text", nullable: true)]
    #[Serializer\Expose()]
    private ?string $hours;

    /**
     * @var mixed[]
     */
    #[ORM\Column(type: "json", nullable: true)]
    #[Serializer\Expose()]
    private ?array $location = null;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getHours(): ?string
    {
        return $this->hours;
    }

    public function setHours(?string $hours): void
    {
        $this->hours = $hours;
    }

    /**
     * @return mixed[]|null
     */
    public function getLocation(): ?array
    {
        return $this->location;
    }

    /**
     * @param mixed[]|null $location
     */
    public function setLocation(?array $location): void
    {
        $this->location = $location;
    }
}
