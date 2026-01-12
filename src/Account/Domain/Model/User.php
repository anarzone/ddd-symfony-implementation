<?php

declare(strict_types=1);

namespace App\Account\Domain\Model;

use App\Inventory\Domain\Model\Stock\Reservation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\UuidV7;

#[ORM\Entity]
#[ORM\Index(name: 'idx_user_email', columns: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /** @var Collection<int, ApiToken> */
    #[ORM\OneToMany(targetEntity: ApiToken::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $apiTokens;

    /** @var Collection<int, Reservation> */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $reservations;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: UuidType::NAME, unique: true)]
        public ?UuidV7 $uuid,
        #[ORM\Column(type: Types::STRING, length: 180)]
        public string $email,
        #[ORM\Column(type: Types::STRING)]
        public string $password,
        UserPasswordHasherInterface $passwordHasher,
        #[ORM\Column(type: Types::JSON)]
        public array $roles,
    ) {
        $this->reservations = new ArrayCollection();
        $this->apiTokens = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();

        $this->password = $passwordHasher->hashPassword($this, $password);
    }

    public function promoteToAdmin(): void
    {
        if (!\in_array('ROLE_ADMIN', $this->roles)) {
            $this->roles[] = 'ROLE_ADMIN';
        }
    }

    public function getUuid(): ?UuidV7
    {
        return $this->uuid;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function changeEmail(string $email): void
    {
        $this->email = $email;
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

    public function getPassword(): string
    {
        return $this->password;
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
