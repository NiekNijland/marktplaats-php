<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Testing;

use DateTimeImmutable;
use NiekNijland\Marktplaats\Data\Category;
use NiekNijland\Marktplaats\Data\CategoryCatalog;

class CategoryCatalogFactory
{
    /**
     * @param  list<Category>|null  $categories
     */
    public static function make(
        ?array $categories = null,
        int $parentCategoryId = 678,
        ?DateTimeImmutable $discoveredAt = null,
    ): CategoryCatalog {
        return new CategoryCatalog(
            categories: $categories ?? [
                CategoryFactory::make(id: 696, key: 'honda', name: 'Honda', fullName: 'Motoren | Honda'),
                CategoryFactory::make(id: 710, key: 'yamaha', name: 'Yamaha', fullName: 'Motoren | Yamaha'),
                CategoryFactory::make(id: 692, key: 'bmw', name: 'BMW', fullName: 'Motoren | BMW'),
            ],
            parentCategoryId: $parentCategoryId,
            discoveredAt: $discoveredAt ?? new DateTimeImmutable,
        );
    }
}
