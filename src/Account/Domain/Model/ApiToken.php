<?php

namespace App\Account\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ApiToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    public string $token;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'apiTokens')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public User $user;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $description = null;

    #[ORM\Column]
    public DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    public ?DateTimeImmutable $expiresAt = null;

    #[ORM\Column(nullable: true)]
    public ?DateTimeImmutable $lastUsedAt = null;

    public function __construct(
        User $user,
        string $token,
        ?string $description = null,
        ?\DateTimeImmutable $expiresAt = null
    ) {
        $this->user = $user;
        $this->token = password_hash($token, PASSWORD_BCRYPT); // Secure hash
        $this->description = $description;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function isValid(): bool
    {
        return $this->expiresAt === null || $this->expiresAt > new DateTimeImmutable();
    }

    public function verify(string $plainToken): bool
    {
        return password_verify($plainToken, $this->token);
    }

    public function markAsUsed(): void
    {
        $this->lastUsedAt = new \DateTimeImmutable();
    }
}
