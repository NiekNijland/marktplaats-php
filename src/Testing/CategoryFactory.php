<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Testing;

use NiekNijland\Marktplaats\Data\Category;

class CategoryFactory
{
    public static function make(
        int $id = 51,
        ?string $key = 'bureaus',
        ?string $name = 'Bureaus',
        ?string $fullName = 'Huis en Inrichting | Bureaus',
        ?int $parentId = 15,
        ?string $parentKey = 'huis-en-inrichting',
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
