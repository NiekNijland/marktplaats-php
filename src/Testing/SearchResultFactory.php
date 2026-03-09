<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Testing;

use NiekNijland\Marktplaats\Data\Category;
use NiekNijland\Marktplaats\Data\Listing;
use NiekNijland\Marktplaats\Data\SearchFacet;
use NiekNijland\Marktplaats\Data\SearchMetaTags;
use NiekNijland\Marktplaats\Data\SearchRequest;
use NiekNijland\Marktplaats\Data\SearchResult;
use NiekNijland\Marktplaats\Data\SortOption;

class SearchResultFactory
{
    /**
     * @param  Listing[]|null  $listings
     * @param  Listing[]|null  $topBlock
     * @param  SearchFacet[]|null  $facets
     * @param  SortOption[]|null  $sortOptions
     * @param  Category[]|null  $searchCategoryOptions
     */
    public static function make(
        ?array $listings = null,
        ?array $topBlock = null,
        ?array $facets = null,
        int $totalResultCount = 1,
        int $maxAllowedPageNumber = 1,
        ?string $correlationId = null,
        ?string $originalQuery = null,
        ?array $sortOptions = null,
        ?int $searchCategory = null,
        ?array $searchCategoryOptions = null,
        ?SearchRequest $searchRequest = null,
        ?SearchMetaTags $metaTags = null,
    ): SearchResult {
        return new SearchResult(
            listings: $listings ?? [ListingFactory::make()],
            topBlock: $topBlock ?? [],
            facets: $facets ?? [],
            totalResultCount: $totalResultCount,
            maxAllowedPageNumber: $maxAllowedPageNumber,
            correlationId: $correlationId,
            originalQuery: $originalQuery,
            sortOptions: $sortOptions ?? [],
            searchCategory: $searchCategory,
            searchCategoryOptions: $searchCategoryOptions ?? [],
            searchRequest: $searchRequest,
            metaTags: $metaTags,
        );
    }
}
