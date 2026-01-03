<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Model\Warehouse;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
readonly class Location
{
    public function __construct(
        #[ORM\Column(type: Types::STRING, length: 100)]
        public string $address,
        #[ORM\Column(type: Types::STRING, length: 50)]
        public string $city,
        #[ORM\Column(type: Types::STRING, length: 10)]
        public string $postalCode,
        #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8, nullable: true)]
        public ?string $latitude,
        #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 8, nullable: true)]
        public ?string $longitude
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->address) || empty($this->city)) {
            throw new \InvalidArgumentException('Address and city are required');
        }

        $lat = is_numeric($this->latitude) ? (float) $this->latitude : null;
        $lng = is_numeric($this->longitude) ? (float) $this->longitude : null;

        if ($lat !== null && ($lat < -90 || $lat > 90)) {
            throw new \InvalidArgumentException('Invalid latitude');
        }

        if ($lng !== null && ($lng < -180 || $lng > 180)) {
            throw new \InvalidArgumentException('Invalid longitude');
        }
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
