<?php

namespace App\Entity;

use App\Repository\OrderLineRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderLineRepository::class)]
class OrderLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'orderLines')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Item $item;

    #[ORM\Column(type: 'float')]
    private float $amount;

    #[ORM\Column(type: 'string', length: 255)]
    private string $description;

    #[ORM\Column(type: 'float')]
    private float $discount;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(type: 'string', length: 255)]
    private string $unitCode;

    #[ORM\Column(type: 'string', length: 255)]
    private string $unitDescription;

    #[ORM\Column(type: 'float')]
    private float $unitPrice;

    #[ORM\Column(type: 'float')]
    private float $vatAmount;

    #[ORM\Column(type: 'float')]
    private float $vatPercentage;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(?Item $item): self
    {
        $this->item = $item;
        return $this;
    }


    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDiscount(): float
    {
        return $this->discount;
    }

    public function setDiscount(float $discount): self
    {
        $this->discount = $discount;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getUnitCode(): string
    {
        return $this->unitCode;
    }

    public function setUnitCode(string $unitCode): self
    {
        $this->unitCode = $unitCode;
        return $this;
    }

    public function getUnitDescription(): string
    {
        return $this->unitDescription;
    }

    public function setUnitDescription(string $unitDescription): self
    {
        $this->unitDescription = $unitDescription;
        return $this;
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(float $unitPrice): self
    {
        $this->unitPrice = $unitPrice;
        return $this;
    }

    public function getVatAmount(): float
    {
        return $this->vatAmount;
    }

    public function setVatAmount(float $vatAmount): self
    {
        $this->vatAmount = $vatAmount;
        return $this;
    }

    public function getVatPercentage(): float
    {
        return $this->vatPercentage;
    }

    public function setVatPercentage(float $vatPercentage): self
    {
        $this->vatPercentage = $vatPercentage;
        return $this;
    }
}
