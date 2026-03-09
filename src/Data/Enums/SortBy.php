<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data\Enums;

enum SortBy: string
{
    case SORT_INDEX = 'SORT_INDEX';
    case PRICE = 'PRICE';
    case OPTIMIZED = 'OPTIMIZED';
}
