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
        int $parentCategoryId = 15,
        ?DateTimeImmutable $discoveredAt = null,
    ): CategoryCatalog {
        return new CategoryCatalog(
            categories: $categories ?? [
                CategoryFactory::make(id: 51, key: 'bureaus', name: 'Bureaus', fullName: 'Huis en Inrichting | Bureaus'),
                CategoryFactory::make(id: 52, key: 'stoelen', name: 'Stoelen', fullName: 'Huis en Inrichting | Stoelen'),
                CategoryFactory::make(id: 53, key: 'kasten', name: 'Kasten', fullName: 'Huis en Inrichting | Kasten'),
            ],
            parentCategoryId: $parentCategoryId,
            discoveredAt: $discoveredAt ?? new DateTimeImmutable,
        );
    }
}
