<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class ListingDetailLocation
{
    public function __construct(
        public ?string $cityName = null,
        public ?string $countryName = null,
        public ?string $countryAbbreviation = null,
        public bool $isAbroad = false,
        public bool $isOnCountryLevel = false,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {}

    /**
     * @return array{cityName: ?string, countryName: ?string, countryAbbreviation: ?string, isAbroad: bool, isOnCountryLevel: bool, latitude: ?float, longitude: ?float}
     */
    public function toArray(): array
    {
        return [
            'cityName' => $this->cityName,
            'countryName' => $this->countryName,
            'countryAbbreviation' => $this->countryAbbreviation,
            'isAbroad' => $this->isAbroad,
            'isOnCountryLevel' => $this->isOnCountryLevel,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            cityName: $data['cityName'] ?? null,
            countryName: $data['countryName'] ?? null,
            countryAbbreviation: $data['countryAbbreviation'] ?? null,
            isAbroad: (bool) ($data['isAbroad'] ?? false),
            isOnCountryLevel: (bool) ($data['isOnCountryLevel'] ?? false),
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
        );
    }
}
