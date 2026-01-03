<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Model\Warehouse;

use App\Inventory\Domain\Model\Stock\Stock;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'inventory_warehouses')]
class Warehouse
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null;

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
    public function isOpen(): bool
    {
        return $this->isActive;
    }

    public function changeType(WarehouseTypeEnum $type): void
    {
        $this->type = $type;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }
}
