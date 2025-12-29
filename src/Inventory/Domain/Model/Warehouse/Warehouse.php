<?php

namespace App\Inventory\Domain\Model\Warehouse;

use App\Inventory\Domain\Model\Stock\Stock;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'inventory_warehouses')]
class Warehouse
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 100)]
    public string $name {
        get => $this->name;
    }

    #[ORM\Column]
    private int $capacity {
        get => $this->capacity;
    }

    #[ORM\Column]
    private bool $isActive = true {
        get => $this->isActive;
    }

    #[ORM\Column(length: 20, enumType: WarehouseTypeEnum::class)]
    public WarehouseTypeEnum $type {
        get => $this->type;
    }

    #[ORM\Column]
    public \DateTimeImmutable $createdAt;

    #[ORM\Embedded(class: Location::class, columnPrefix: false)]
    private Location $location {
        get => $this->location;
    }

    /** @var Collection<int, Stock> */
    #[ORM\OneToMany(targetEntity: Stock::class, mappedBy: 'warehouse')]
    public Collection $stocks {
        get => $this->stocks;
    }

    public function __construct(string $name, int $capacity, Location $location)
    {
        $this->name = $name;
        $this->capacity = $capacity;
        $this->location = $location;
        $this->createdAt = new \DateTimeImmutable();
        $this->type = WarehouseTypeEnum::Standard;
        $this->stocks = new ArrayCollection();
    }

    // Domain Logic
    public function isOpen(): bool { return $this->isActive; }

    public function changeType(WarehouseTypeEnum $type): void
    {
        $this->type = $type;
    }
}
