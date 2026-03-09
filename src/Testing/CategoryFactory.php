<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Testing;

use NiekNijland\Marktplaats\Data\Category;

class CategoryFactory
{
    public static function make(
        int $id = 696,
        ?string $key = 'honda',
        ?string $name = 'Honda',
        ?string $fullName = 'Motoren | Honda',
        ?int $parentId = 678,
        ?string $parentKey = 'motoren',
    ): Category {
        return new Category(
            id: $id,
            key: $key,
            name: $name,
            fullName: $fullName,
            parentId: $parentId,
            parentKey: $parentKey,
        );
    }
}
