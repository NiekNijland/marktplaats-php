<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Testing;

use DateTimeImmutable;
use NiekNijland\Marktplaats\Data\MotorcycleBrand;
use NiekNijland\Marktplaats\Data\MotorcycleBrandCatalog;

class MotorcycleBrandCatalogFactory
{
    /**
     * @param  MotorcycleBrand[]|null  $brands
     */
    public static function make(
        ?array $brands = null,
        int $sourceCategoryId = 678,
        ?DateTimeImmutable $discoveredAt = null,
    ): MotorcycleBrandCatalog {
        return new MotorcycleBrandCatalog(
            brands: $brands ?? [
                MotorcycleBrandFactory::make(categoryId: 696, key: 'honda', name: 'Honda', fullName: 'Motoren | Honda'),
                MotorcycleBrandFactory::make(categoryId: 710, key: 'yamaha', name: 'Yamaha', fullName: 'Motoren | Yamaha'),
                MotorcycleBrandFactory::make(categoryId: 692, key: 'bmw', name: 'BMW', fullName: 'Motoren | BMW'),
            ],
            sourceCategoryId: $sourceCategoryId,
            discoveredAt: $discoveredAt ?? new DateTimeImmutable,
        );
    }
}
