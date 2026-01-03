<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Model\Stock;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
readonly class SKU
{
    public function __construct(
        #[ORM\Column(type: Types::STRING, length: 50)]
        public string $code,
        #[ORM\Column(type: Types::STRING, length: 255)]
        public string $name
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->code) && \strlen($this->code) < 3) {
            throw new \InvalidArgumentException('SKU code must be at least 3 characters');
        }
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }

    public function __toString(): string
    {
        return \sprintf('%s - %s', $this->code, $this->name);
    }
}
