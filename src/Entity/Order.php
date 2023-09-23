<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid', unique: true)]
    private ?string $id = null;

    #[ORM\Column(type: 'integer')]
    private int $orderNumber;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $amount;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(name: 'deliver_to_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Contact $deliverTo = null;

    #[ORM\OneToMany(targetEntity: OrderLine::class, mappedBy: 'order', cascade: ['persist'])]
    private Collection $salesOrderLines;

    public function __construct()
    {
        $this->salesOrderLines = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getOrderNumber(): int
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(int $orderNumber): self
    {
        $this->orderNumber = $orderNumber;
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

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getDeliverTo(): ?Contact
    {
        return $this->deliverTo;
    }

    public function setDeliverTo(?Contact $deliverTo): self
    {
        $this->deliverTo = $deliverTo;
        return $this;
    }

    /**
     * @return Collection|OrderLine[]
     */
    public function getSalesOrderLines(): Collection
    {
        return $this->salesOrderLines;
    }

    public function setSalesOrderLine(array $salesOrderLines): self
    {
        foreach ($salesOrderLines as $salesOrderLine) {
            if (!$this->salesOrderLines->contains($salesOrderLine)) {
                $this->salesOrderLines[] = $salesOrderLine;
                $salesOrderLine->setOrder($this);
            }
        }

        return $this;
    }

    public function removeSalesOrderLine(OrderLine $salesOrderLine): self
    {
        if ($this->salesOrderLines->removeElement($salesOrderLine)) {
            // set the owning side to null (unless already changed)
            if ($salesOrderLine->getOrder() === $this) {
                $salesOrderLine->setOrder(null);
            }
        }

        return $this;
    }
}