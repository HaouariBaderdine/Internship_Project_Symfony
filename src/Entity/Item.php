<?php

namespace App\Entity;

use App\Repository\ItemRepository;
use Doctrine\ORM\Mapping as ORM;

use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;


#[ORM\Entity(repositoryClass: ItemRepository::class)]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'uuid_binary_ordered_time')]
    private ?string $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?float $price = null;

    #[ORM\Column(type: 'string', length: 10)]
    private ?string $unitCode = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $unitDescription = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?float $vatAmount = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private ?float $vatPercentage = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getUnitCode(): ?string
    {
        return $this->unitCode;
    }

    public function setUnitCode(string $unitCode): self
    {
        $this->unitCode = $unitCode;

        return $this;
    }

    public function getUnitDescription(): ?string
    {
        return $this->unitDescription;
    }

    public function setUnitDescription(string $unitDescription): self
    {
        $this->unitDescription = $unitDescription;

        return $this;
    }

    public function getVatAmount(): ?float
    {
        return $this->vatAmount;
    }

    public function setVatAmount(float $vatAmount): self
    {
        $this->vatAmount = $vatAmount;

        return $this;
    }

    public function getVatPercentage(): ?float
    {
        return $this->vatPercentage;
    }

    public function setVatPercentage(float $vatPercentage): self
    {
        $this->vatPercentage = $vatPercentage;

        return $this;
    }
}
