<?php

declare(strict_types=1);

namespace App\Account\Domain\Model;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class ApiToken
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: UuidType::NAME, unique: true)]
        public ?Uuid $uuid,
        #[ORM\Column(length: 64, unique: true)]
        public string $token,
        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'apiTokens')]
        #[ORM\JoinColumn(referencedColumnName: 'uuid', nullable: false, onDelete: 'CASCADE')]
        public User $user,
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $description,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
        public private(set) ?\DateTimeImmutable $createdAt = null,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
        public ?\DateTimeImmutable $expiresAt = null,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
        public ?\DateTimeImmutable $lastUsedAt = null,
    ) {
        $this->token = password_hash($this->token, \PASSWORD_BCRYPT);
        $this->createdAt = new \DateTimeImmutable();
    }

    public function isValid(): bool
    {
        return $this->expiresAt === null || $this->expiresAt > new \DateTimeImmutable();
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
