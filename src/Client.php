<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats;

use Generator;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;
use JsonException;
use NiekNijland\Marktplaats\Data\Listing;
use NiekNijland\Marktplaats\Data\ListingDetail;
use NiekNijland\Marktplaats\Data\MotorcycleBrandCatalog;
use NiekNijland\Marktplaats\Data\MotorcycleSearchQuery;
use NiekNijland\Marktplaats\Data\SearchQuery;
use NiekNijland\Marktplaats\Data\SearchResult;
use NiekNijland\Marktplaats\Exception\ClientException;
use NiekNijland\Marktplaats\Parser\ListingDetailParser;
use NiekNijland\Marktplaats\Parser\SearchParser;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class Client implements ClientInterface
{
    private const string BASE_URL = 'https://www.marktplaats.nl';

    private SearchParser $searchParser;

    private ListingDetailParser $listingDetailParser;

    public function __construct(
        private readonly HttpClientInterface $httpClient = new GuzzleClient,
        private readonly ?CacheInterface $cache = null,
        private readonly int $cacheTtl = 3600,
    ) {
        $this->searchParser = new SearchParser;
        $this->listingDetailParser = new ListingDetailParser;
    }

    public function getSearch(SearchQuery $query): SearchResult
    {
        $cacheKey = $query->buildCacheKey();

        if (($cached = $this->fetchFromCache($cacheKey)) instanceof SearchResult) {
            return $cached;
        }

        $result = $this->fetchSearchResult($query);

        $this->storeInCache($cacheKey, $result);

        return $result;
    }

    /**
     * Yields all listings by paginating through search results.
     *
     * Always fetches live from the API (bypasses cache) to avoid stale
     * pagination metadata causing early termination or over-iteration.
     *
     * @return Generator<int, Listing>
     */
    public function getSearchAll(SearchQuery $query): Generator
    {
        $offset = $query->offset;
        $yieldedCount = 0;
        $previousItemIds = [];
        $maxIterations = 1000;
        $iteration = 0;

        while (true) {
            if (++$iteration > $maxIterations) {
                break;
            }

            $currentQuery = $query->withOffset($offset);
            $result = $this->fetchSearchResult($currentQuery);

            if ($result->listings === []) {
                break;
            }

            $currentItemIds = array_map(fn (Listing $l): string => $l->itemId, $result->listings);

            if ($currentItemIds === $previousItemIds) {
                break;
            }

            $previousItemIds = $currentItemIds;

            foreach ($result->listings as $listing) {
                yield $yieldedCount => $listing;
                $yieldedCount++;
            }

            $offset += $currentQuery->limit;

            if ($yieldedCount >= $result->totalResultCount) {
                break;
            }

            $safetyBoundary = $result->maxAllowedPageNumber * $currentQuery->limit;

            if ($safetyBoundary > 0 && $offset >= $safetyBoundary) {
                break;
            }
        }
    }

    public function getMotorcycleSearch(MotorcycleSearchQuery $query): SearchResult
    {
        $result = $this->getSearch($query);

        if (! $query->strictMode) {
            return $result;
        }

        return $this->applyStrictMotorcycleFilter($result);
    }

    public function getMotorcycleBrandCatalog(): MotorcycleBrandCatalog
    {
        $cacheKey = 'marktplaats:brands:'.MotorcycleSearchQuery::MOTORCYCLE_ROOT_CATEGORY;

        if (($cached = $this->fetchBrandCatalogFromCache($cacheKey)) instanceof MotorcycleBrandCatalog) {
            return $cached;
        }

        $discoveryQuery = new SearchQuery(
            l1CategoryId: MotorcycleSearchQuery::MOTORCYCLE_ROOT_CATEGORY,
            limit: 1,
        );

        $url = $discoveryQuery->buildUrl();
        $body = $this->fetchRawResponse($url);

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ClientException('Failed to decode brand catalog response: '.$e->getMessage(), 0, $e);
        }

        $catalog = $this->searchParser->parseMotorcycleBrandCatalog(
            $data,
            MotorcycleSearchQuery::MOTORCYCLE_ROOT_CATEGORY,
        );

        $this->storeBrandCatalogInCache($cacheKey, $catalog);

        return $catalog;
    }

    public function getListing(string $url): ListingDetail
    {
        $fullUrl = $this->resolveListingUrl($url);
        $cacheKey = $this->buildListingDetailCacheKey($fullUrl);

        if (($cached = $this->fetchListingDetailFromCache($cacheKey)) instanceof ListingDetail) {
            return $cached;
        }

        $html = $this->fetchRawResponse($fullUrl);
        $detail = $this->listingDetailParser->parseHtml($html, $fullUrl);

        $this->storeListingDetailInCache($cacheKey, $detail);

        return $detail;
    }

    public function resetSession(): void
    {
        // Reserved for future session/cookie state clearing.
    }

    private function resolveListingUrl(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        if (str_starts_with($url, '/')) {
            return self::BASE_URL.$url;
        }

        return self::BASE_URL.'/'.$url;
    }

    private function fetchSearchResult(SearchQuery $query): SearchResult
    {
        $url = $query->buildUrl();
        $body = $this->fetchRawResponse($url);

        return $this->searchParser->parseJson($body);
    }

    private function fetchRawResponse(string $url): string
    {
        try {
            $request = new Request('GET', $url);
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new ClientException('HTTP request failed: '.$e->getMessage(), 0, $e);
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode === 204) {
            return '{}';
        }

        if ($statusCode === 400) {
            throw new ClientException('Marktplaats API returned 400 Bad Request', $statusCode);
        }

        if ($statusCode === 401 || $statusCode === 403) {
            throw new ClientException('Marktplaats API authorization error (HTTP '.$statusCode.')', $statusCode);
        }

        if ($statusCode === 404) {
            throw new ClientException('Marktplaats API endpoint not found (HTTP 404)', $statusCode);
        }

        if ($statusCode === 429) {
            throw new ClientException('Marktplaats API rate limit exceeded (HTTP 429)', $statusCode);
        }

        if ($statusCode >= 500) {
            throw new ClientException('Marktplaats API server error (HTTP '.$statusCode.')', $statusCode);
        }

        if ($statusCode !== 200) {
            throw new ClientException('Unexpected HTTP status code: '.$statusCode, $statusCode);
        }

        return (string) $response->getBody();
    }

    private function applyStrictMotorcycleFilter(SearchResult $result): SearchResult
    {
        $filtered = array_values(array_filter(
            $result->listings,
            fn (Listing $listing): bool => $listing->categoryId !== null
                && ! in_array($listing->categoryId, MotorcycleSearchQuery::STRICT_MODE_EXCLUDED_CATEGORIES, true),
        ));

        return new SearchResult(
            listings: $filtered,
            topBlock: $result->topBlock,
            facets: $result->facets,
            totalResultCount: $result->totalResultCount,
            maxAllowedPageNumber: $result->maxAllowedPageNumber,
            correlationId: $result->correlationId,
            originalQuery: $result->originalQuery,
            sortOptions: $result->sortOptions,
            searchCategory: $result->searchCategory,
            searchCategoryOptions: $result->searchCategoryOptions,
            searchRequest: $result->searchRequest,
            metaTags: $result->metaTags,
        );
    }

    private function fetchFromCache(string $key): ?SearchResult
    {
        if (! $this->cache instanceof CacheInterface) {
            return null;
        }

        try {
            /** @var array<string, mixed>|null $cached */
            $cached = $this->cache->get($key);
        } catch (InvalidArgumentException) {
            return null;
        }

        if (! is_array($cached)) {
            return null;
        }

        return SearchResult::fromArray($cached);
    }

    private function storeInCache(string $key, SearchResult $result): void
    {
        if (! $this->cache instanceof CacheInterface) {
            return;
        }

        try {
            $this->cache->set($key, $result->toArray(), $this->cacheTtl);
        } catch (InvalidArgumentException) {
            // Silently ignore cache write failures.
        }
    }

    private function fetchBrandCatalogFromCache(string $key): ?MotorcycleBrandCatalog
    {
        if (! $this->cache instanceof CacheInterface) {
            return null;
        }

        try {
            /** @var array<string, mixed>|null $cached */
            $cached = $this->cache->get($key);
        } catch (InvalidArgumentException) {
            return null;
        }

        if (! is_array($cached)) {
            return null;
        }

        return MotorcycleBrandCatalog::fromArray($cached);
    }

    private function storeBrandCatalogInCache(string $key, MotorcycleBrandCatalog $catalog): void
    {
        if (! $this->cache instanceof CacheInterface) {
            return;
        }

        try {
            $this->cache->set($key, $catalog->toArray(), $this->cacheTtl);
        } catch (InvalidArgumentException) {
            // Silently ignore cache write failures.
        }
    }

    private function buildListingDetailCacheKey(string $url): string
    {
        return 'marktplaats:listing:'.md5($url);
    }

    private function fetchListingDetailFromCache(string $key): ?ListingDetail
    {
        if (! $this->cache instanceof CacheInterface) {
            return null;
        }

        try {
            /** @var array<string, mixed>|null $cached */
            $cached = $this->cache->get($key);
        } catch (InvalidArgumentException) {
            return null;
        }

        if (! is_array($cached)) {
            return null;
        }

        return ListingDetail::fromArray($cached);
    }

    private function storeListingDetailInCache(string $key, ListingDetail $detail): void
    {
        if (! $this->cache instanceof CacheInterface) {
            return;
        }

        try {
            $this->cache->set($key, $detail->toArray(), $this->cacheTtl);
        } catch (InvalidArgumentException) {
            // Silently ignore cache write failures.
        }
    }
}
