<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Testing;

use NiekNijland\Marktplaats\Data\Location;

class LocationFactory
{
    public static function make(
        ?string $cityName = 'Amsterdam',
        ?string $countryName = 'Nederland',
        ?string $countryAbbreviation = 'NL',
        ?int $distanceMeters = null,
        bool $isBuyerLocation = false,
        bool $onCountryLevel = false,
        bool $abroad = false,
        ?float $latitude = null,
        ?float $longitude = null,
    ): Location {
        return new Location(
            cityName: $cityName,
            countryName: $countryName,
            countryAbbreviation: $countryAbbreviation,
            distanceMeters: $distanceMeters,
            isBuyerLocation: $isBuyerLocation,
            onCountryLevel: $onCountryLevel,
            abroad: $abroad,
            latitude: $latitude,
            longitude: $longitude,
        );
    }
}
