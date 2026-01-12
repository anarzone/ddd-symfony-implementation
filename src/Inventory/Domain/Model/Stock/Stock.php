<?php

declare(strict_types=1);

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
use Symfony\Component\Uid\UuidV7;

#[ORM\Entity(repositoryClass: StockRepository::class)]
#[ORM\Table(name: 'inventory_stocks')]
#[ORM\Index(name: 'idx_stock_sku', columns: ['sku_code'])]
class Stock
{
    /** @var Collection<int, Reservation> */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'stock', cascade: ['persist', 'remove'])]
    private Collection $reservations;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: UuidType::NAME, unique: true)]
        public ?Uuid $uuid,
        #[ORM\Embedded(class: SKU::class)]
        public SKU $sku,
        #[ORM\Column(type: Types::INTEGER)]
        public int $totalQuantity,
        #[ORM\ManyToOne(targetEntity: Warehouse::class, inversedBy: 'stocks')]
        #[ORM\JoinColumn(referencedColumnName: 'uuid', nullable: false)]
        public Warehouse $warehouse,
    ) {
        $this->reservations = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getReservedQuantity(): int
    {
        return array_reduce(
            $this->reservations->toArray(),
            fn (int $carry, Reservation $r) => $r->isActive() ? $carry + $r->quantity : $carry,
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
                \sprintf(
                    'Only %d units available, cannot reserve %d',
                    $this->getAvailableQuantity(),
                    $quantity
                )
            );
        }

        $reservation = new Reservation(
            uuid: new UuidV7(),
            stock: $this,
            quantity: $quantity,
            user: $user,
            minutesValid: $minutesValid
        );
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
                \sprintf(
                    'Cannot reduce quantity below reserved amount (%d)',
                    $reservedQuantity
                )
            );
        }

        $this->totalQuantity = $newQuantity;
    }
}
