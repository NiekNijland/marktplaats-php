<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Testing;

use NiekNijland\Marktplaats\Data\MotorcycleBrand;

class MotorcycleBrandFactory
{
    public static function make(
        int $categoryId = 696,
        string $key = 'honda',
        string $name = 'Honda',
        string $fullName = 'Motoren | Honda',
        int $parentCategoryId = 678,
    ): MotorcycleBrand {
        return new MotorcycleBrand(
            categoryId: $categoryId,
            key: $key,
            name: $name,
            fullName: $fullName,
            parentCategoryId: $parentCategoryId,
        );
    }
}
