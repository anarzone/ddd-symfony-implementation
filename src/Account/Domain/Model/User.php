<?php

declare(strict_types=1);

namespace App\Account\Domain\Model;

use App\Inventory\Domain\Model\Stock\Reservation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Index(name: 'idx_user_email', columns: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: Types::JSON)]
    private array $roles;

    #[ORM\Column(type: Types::STRING)]
    public string $password;

    /** @var Collection<int, ApiToken> */
    #[ORM\OneToMany(targetEntity: ApiToken::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $apiTokens;

    /** @var Collection<int, Reservation> */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $reservations;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $email, string $password, array $roles = ['ROLE_USER'])
    {
        $this->email = $email;
        $this->hashPassword($password);
        $this->roles = $roles;
        $this->reservations = new ArrayCollection();
        $this->apiTokens = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function promoteToAdmin(): void
    {
        if (!\in_array('ROLE_ADMIN', $this->roles)) {
            $this->roles[] = 'ROLE_ADMIN';
        }
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function hashPassword(string $password): static
    {
        $this->password = hash('sha256', $password);

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function activeReservations(): Collection
    {
        return $this->reservations->filter(
            fn (Reservation $r) => $r->isActive()
        );
    }
}
