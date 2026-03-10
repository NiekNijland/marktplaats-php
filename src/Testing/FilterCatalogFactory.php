<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Testing;

use DateTimeImmutable;
use NiekNijland\Marktplaats\Data\Enums\SearchFacetType;
use NiekNijland\Marktplaats\Data\FilterCatalog;
use NiekNijland\Marktplaats\Data\SearchFacet;
use NiekNijland\Marktplaats\Data\SearchFacetAttributeGroupOption;

class FilterCatalogFactory
{
    /**
     * @param  list<SearchFacet>|null  $facets
     */
    public static function make(
        ?array $facets = null,
        int $categoryId = 15,
        ?int $subCategoryId = null,
        ?DateTimeImmutable $discoveredAt = null,
    ): FilterCatalog {
        return new FilterCatalog(
            facets: $facets ?? [
                new SearchFacet(
                    id: 100,
                    key: 'constructionYear',
                    type: SearchFacetType::ATTRIBUTE_RANGE,
                    rawType: null,
                    label: null,
                    singleSelect: null,
                    categoryId: null,
                    categories: [],
                    attributeGroup: [],
                ),
                new SearchFacet(
                    id: 200,
                    key: 'brand',
                    type: SearchFacetType::ATTRIBUTE_GROUP,
                    rawType: null,
                    label: 'Merk',
                    singleSelect: true,
                    categoryId: 15,
                    categories: [],
                    attributeGroup: [
                        new SearchFacetAttributeGroupOption(
                            attributeValueKey: 'ikea',
                            attributeValueId: 51,
                            attributeValueLabel: 'IKEA',
                            histogramCount: 1200,
                            selected: false,
                            isValuableForSeo: true,
                            default: false,
                        ),
                    ],
                ),
            ],
            categoryId: $categoryId,
            subCategoryId: $subCategoryId,
            discoveredAt: $discoveredAt ?? new DateTimeImmutable,
        );
    }
}
