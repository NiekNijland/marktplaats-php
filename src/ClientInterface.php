<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats;

use Generator;
use NiekNijland\Marktplaats\Data\CategoryCatalog;
use NiekNijland\Marktplaats\Data\FilterCatalog;
use NiekNijland\Marktplaats\Data\Listing;
use NiekNijland\Marktplaats\Data\ListingDetail;
use NiekNijland\Marktplaats\Data\SearchQuery;
use NiekNijland\Marktplaats\Data\SearchResult;
use NiekNijland\Marktplaats\Exception\ClientException;

interface ClientInterface
{
    /**
     * @param  list<int>  $excludedCategoryIds
     *
     * @throws ClientException
     */
    public function getSearch(SearchQuery $query, array $excludedCategoryIds = []): SearchResult;

    /**
     * @param  list<int>  $excludedCategoryIds
     * @return Generator<int, Listing>
     *
     * @throws ClientException
     */
    public function getSearchAll(SearchQuery $query, array $excludedCategoryIds = []): Generator;

    /**
     * Returns live subcategories discovered from API metadata for a category.
     *
     * @throws ClientException
     */
    public function getCategoryCatalog(int $categoryId): CategoryCatalog;

    /**
     * Returns available search facets for a category/subcategory combination.
     *
     * @throws ClientException
     */
    public function getFilterCatalog(int $categoryId, ?int $subCategoryId = null): FilterCatalog;

    /**
     * Fetches the full detail page for a single listing.
     *
     * Accepts a full URL (https://www.marktplaats.nl/v/...) or a relative
     * vipUrl path (/v/...) as returned in search result Listing objects.
     *
     * @throws ClientException
     */
    public function getListing(string $url): ListingDetail;

    /**
     * Clears session cookies held by the client instance.
     */
    public function resetSession(): void;
}
