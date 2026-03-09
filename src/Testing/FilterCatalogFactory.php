<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Testing;

use DateTimeImmutable;
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
        int $l1CategoryId = 678,
        ?int $l2CategoryId = null,
        ?DateTimeImmutable $discoveredAt = null,
    ): FilterCatalog {
        return new FilterCatalog(
            facets: $facets ?? [
                new SearchFacet(
                    id: 100,
                    key: 'constructionYear',
                    type: 'AttributeRangeFacet',
                    label: null,
                    singleSelect: null,
                    categoryId: null,
                    categories: [],
                    attributeGroup: [],
                ),
                new SearchFacet(
                    id: 200,
                    key: 'brand',
                    type: 'AttributeGroupFacet',
                    label: 'Merk',
                    singleSelect: true,
                    categoryId: 678,
                    categories: [],
                    attributeGroup: [
                        new SearchFacetAttributeGroupOption(
                            attributeValueKey: 'honda',
                            attributeValueId: 696,
                            attributeValueLabel: 'Honda',
                            histogramCount: 1200,
                            selected: false,
                            isValuableForSeo: true,
                            default: false,
                        ),
                    ],
                ),
            ],
            l1CategoryId: $l1CategoryId,
            l2CategoryId: $l2CategoryId,
            discoveredAt: $discoveredAt ?? new DateTimeImmutable,
        );
    }
}
