<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class Location
{
    public function __construct(
        public ?string $cityName,
        public ?string $countryName,
        public ?string $countryAbbreviation,
        public ?int $distanceMeters,
        public bool $isBuyerLocation,
        public bool $onCountryLevel,
        public bool $abroad,
        public ?float $latitude,
        public ?float $longitude,
    ) {}

    /**
     * @return array{cityName: ?string, countryName: ?string, countryAbbreviation: ?string, distanceMeters: ?int, isBuyerLocation: bool, onCountryLevel: bool, abroad: bool, latitude: ?float, longitude: ?float}
     */
    public function toArray(): array
    {
        return [
            'cityName' => $this->cityName,
            'countryName' => $this->countryName,
            'countryAbbreviation' => $this->countryAbbreviation,
            'distanceMeters' => $this->distanceMeters,
            'isBuyerLocation' => $this->isBuyerLocation,
            'onCountryLevel' => $this->onCountryLevel,
            'abroad' => $this->abroad,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    /**
     * @param  array{cityName?: ?string, countryName?: ?string, countryAbbreviation?: ?string, distanceMeters?: ?int, isBuyerLocation?: bool, onCountryLevel?: bool, abroad?: bool, latitude?: ?float, longitude?: ?float}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            cityName: $data['cityName'] ?? null,
            countryName: $data['countryName'] ?? null,
            countryAbbreviation: $data['countryAbbreviation'] ?? null,
            distanceMeters: $data['distanceMeters'] ?? null,
            isBuyerLocation: $data['isBuyerLocation'] ?? false,
            onCountryLevel: $data['onCountryLevel'] ?? false,
            abroad: $data['abroad'] ?? false,
            latitude: $data['latitude'] ?? null,
            longitude: $data['longitude'] ?? null,
        );
    }
}
