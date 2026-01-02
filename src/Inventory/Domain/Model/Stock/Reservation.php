<?php

namespace App\Inventory\Domain\Model\Stock;

use App\Account\Domain\Model\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'inventory_reservations')]
#[ORM\Index(name: 'idx_reservation_expiry', columns: ['expires_at'])]
class Reservation
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null;

    // Link to the Aggregate Root (Stock)
    #[ORM\ManyToOne(targetEntity: Stock::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Stock $stock {
        get => $this->stock;
    }

    #[ORM\Column]
    public int $quantity{
        get => $this->quantity;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public User $user {
        get => $this->user;
    }

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $orderReference = null; // Link to an Order ID once payment starts

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    public \DateTimeImmutable $expiresAt;

    #[ORM\Column(length: 20)]
    private string $status { // ACTIVE, CONVERTED_TO_SALE, EXPIRED, CANCELLED
        get => $this->status;
    }

    public function __construct(Stock $stock, int $quantity, User $user, int $minutesValid = 15)
    {
        $this->stock = $stock;
        $this->quantity = $quantity;
        $this->user = $user;
        $this->status = 'ACTIVE';
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = $this->createdAt->modify("+$minutesValid minutes");
    }

    public function isExpired(): bool
    {
        return new \DateTimeImmutable() > $this->expiresAt;
    }

    public function cancel(): void
    {
        $this->status = 'CANCELLED';
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE' && !$this->isExpired();
    }

    public function convertToSale(string $orderReference)
    {
        if ($this->isExpired()) {
            throw new \DomainException('Cannot convert expired reservation to sale');
        }

        $this->status = 'CONVERTED_TO_SALE';
        $this->orderReference = $orderReference;
    }
}
