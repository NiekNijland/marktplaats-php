<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data\Enums;

enum SearchFacetType: string
{
    case ATTRIBUTE_GROUP = 'AttributeGroupFacet';
    case ATTRIBUTE_RANGE = 'AttributeRangeFacet';
    case CATEGORY_TREE = 'CategoryTreeFacet';
}
