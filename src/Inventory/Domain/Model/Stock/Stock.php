<?php

namespace App\Inventory\Domain\Model\Stock;

use App\Inventory\Domain\Exception\InsufficientStockException;
use App\Inventory\Domain\Model\Warehouse\Warehouse;
use App\Inventory\Infrastructure\Persistence\Doctrine\StockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockRepository::class)]
#[ORM\Table(name: 'inventory_stocks')]
#[ORM\Index(name: 'idx_stock_sku', columns: ['sku_code'])]
class Stock
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null {
        get =>  $this->id;
    }

    #[ORM\Embedded(class: SKU::class)]
    public SKU $sku;

    #[ORM\Column(type: Types::INTEGER)]
    private int $totalQuantity {
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
    public Collection $reservations {
        get => $this->reservations;
    }

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
    public function reserve(int $quantity, Reservation $reservation): void
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

        $reservation = new Reservation($this, $quantity, $reservation->user, $reservation->expiresAt);
        $this->reservations->add($reservation);
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
