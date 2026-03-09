<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats;

use Generator;
use NiekNijland\Marktplaats\Data\CategoryCatalog;
use NiekNijland\Marktplaats\Data\Listing;
use NiekNijland\Marktplaats\Data\ListingDetail;
use NiekNijland\Marktplaats\Data\SearchQuery;
use NiekNijland\Marktplaats\Data\SearchResult;
use NiekNijland\Marktplaats\Exception\ClientException;

interface ClientInterface
{
    /**
     * @throws ClientException
     */
    public function getSearch(SearchQuery $query): SearchResult;

    /**
     * @return Generator<int, Listing>
     *
     * @throws ClientException
     */
    public function getSearchAll(SearchQuery $query): Generator;

    /**
     * Returns live subcategories discovered from API metadata for an L1 category.
     *
     * @throws ClientException
     */
    public function getCategoryCatalog(int $l1CategoryId): CategoryCatalog;

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
     * Clears cache/session state held by the client instance.
     */
    public function resetSession(): void;
}
