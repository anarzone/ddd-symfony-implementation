<?php

namespace App\Inventory\Domain\Model\Stock;

use App\Account\Domain\Model\User;
use App\Inventory\Domain\Exception\InsufficientStockException;
use App\Inventory\Domain\Model\Warehouse\Warehouse;
use App\Inventory\Infrastructure\Persistence\Doctrine\StockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: StockRepository::class)]
#[ORM\Table(name: 'inventory_stocks')]
#[ORM\Index(name: 'idx_stock_sku', columns: ['sku_code'])]
class Stock
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null;

    #[ORM\Embedded(class: SKU::class)]
    public SKU $sku;

    #[ORM\Column(type: Types::INTEGER)]
    public int $totalQuantity {
        get => $this->totalQuantity;

        set(int $quantity) {
            if ($quantity < 0){
                throw new \InvalidArgumentException('Total quantity cannot be negative');
            }
            $this->totalQuantity = $quantity;
        }
    }

    #[ORM\ManyToOne(targetEntity: Warehouse::class, inversedBy: 'stocks')]
    #[ORM\JoinColumn(nullable: false)]
    public Warehouse $warehouse {
        get => $this->warehouse;
    }

    /** @var Collection<int, Reservation> */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'stock', cascade: ['persist', 'remove'])]
    public Collection $reservations;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    public function __construct(Warehouse $warehouse, SKU $sku, int $totalQuantity)
    {
        $this->warehouse = $warehouse;
        $this->sku = $sku;
        $this->totalQuantity = $totalQuantity;
        $this->reservations = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getReservedQuantity(): int
    {
        return array_reduce(
            $this->reservations->toArray(),
            fn(int $carry, Reservation $r) => $r->isActive() ? $carry + $r->quantity : $carry,
            0
        );
    }

    public function getAvailableQuantity(): int
    {
        return $this->totalQuantity - $this->getReservedQuantity();
    }

    /**
     * @throws InsufficientStockException
     */
    public function reserve(int $quantity, User $user, int $minutesValid = 15): Reservation
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Reservation quantity must be positive');
        }

        if ($this->getAvailableQuantity() < $quantity) {
            throw new InsufficientStockException(
                sprintf(
                    'Only %d units available, cannot reserve %d',
                    $this->getAvailableQuantity(),
                    $quantity
                )
            );
        }

        $reservation = new Reservation($this, $quantity, $user, $minutesValid);
        $this->reservations->add($reservation);

        return $reservation;
    }

    /**
     * @throws InsufficientStockException
     */
    public function adjustQuantity(int $newQuantity): void
    {
        $reservedQuantity = $this->getReservedQuantity();

        if ($newQuantity < $reservedQuantity) {
            throw new InsufficientStockException(
                sprintf(
                    'Cannot reduce quantity below reserved amount (%d)',
                    $reservedQuantity
                )
            );
        }

        $this->totalQuantity = $newQuantity;
    }
}
