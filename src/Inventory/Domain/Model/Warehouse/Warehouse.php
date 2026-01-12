<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Model\Warehouse;

use App\Inventory\Domain\Model\Stock\Stock;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'inventory_warehouses')]
class Warehouse
{
    /** @var Collection<int, Stock> */
    #[ORM\OneToMany(targetEntity: Stock::class, mappedBy: 'warehouse')]
    private Collection $stocks;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: UuidType::NAME, unique: true)]
        public ?Uuid $uuid,
        #[ORM\Column(length: 100)]
        public string $name,
        #[ORM\Column]
        public int $capacity,
        #[ORM\Embedded(class: Location::class, columnPrefix: false)]
        public Location $location,
        #[ORM\Column(length: 20, enumType: WarehouseTypeEnum::class)]
        public WarehouseTypeEnum $type,
        #[ORM\Column]
        private ?bool $isActive = true,
    ) {
        $this->type = WarehouseTypeEnum::Standard;
        $this->stocks = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
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
}
