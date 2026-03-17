<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats;

use DateTimeImmutable;
use Generator;
use GuzzleHttp\Client as GuzzleClient;
use JsonException;
use NiekNijland\Marktplaats\Data\CategoryCatalog;
use NiekNijland\Marktplaats\Data\FilterCatalog;
use NiekNijland\Marktplaats\Data\Listing;
use NiekNijland\Marktplaats\Data\ListingDetail;
use NiekNijland\Marktplaats\Data\SearchQuery;
use NiekNijland\Marktplaats\Data\SearchResult;
use NiekNijland\Marktplaats\Exception\ClientException;
use NiekNijland\Marktplaats\Parser\ListingDetailParser;
use NiekNijland\Marktplaats\Parser\SearchParser;
use NiekNijland\Marktplaats\Support\CacheStore;
use NiekNijland\Marktplaats\Support\ClockInterface;
use NiekNijland\Marktplaats\Support\HttpTransport;
use NiekNijland\Marktplaats\Support\SystemClock;
use NiekNijland\Marktplaats\Support\UrlResolver;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\SimpleCache\CacheInterface;

class Client implements ClientInterface
{
    private SearchParser $searchParser;

    private ListingDetailParser $listingDetailParser;

    private readonly HttpTransport $transport;

    private readonly CacheStore $cacheStore;

    /**
     * @param  array<string, string>  $defaultHeaders
     */
    public function __construct(
        ?HttpClientInterface $httpClient = null,
        ?CacheInterface $cache = null,
        private readonly int $cacheTtl = 3600,
        private readonly float $requestTimeoutSeconds = 10.0,
        private readonly int $maxRetries = 0,
        private readonly int $retryDelayMilliseconds = 200,
        private readonly array $defaultHeaders = [],
        ?ClockInterface $clock = null,
        private readonly int $requestDelayMilliseconds = 0,
        private readonly int $requestDelayJitterMilliseconds = 0,
        private readonly int $maxRequestsPerWindow = 0,
        private readonly ?string $proxyUrl = null,
    ) {
        if ($this->requestTimeoutSeconds <= 0) {
            throw new ClientException('requestTimeoutSeconds must be greater than 0');
        }

        if ($this->maxRetries < 0) {
            throw new ClientException('maxRetries must be 0 or greater');
        }

        if ($this->retryDelayMilliseconds < 0) {
            throw new ClientException('retryDelayMilliseconds must be 0 or greater');
        }

        if ($this->requestDelayMilliseconds < 0) {
            throw new ClientException('requestDelayMilliseconds must be 0 or greater');
        }

        if ($this->requestDelayJitterMilliseconds < 0) {
            throw new ClientException('requestDelayJitterMilliseconds must be 0 or greater');
        }

        if ($this->maxRequestsPerWindow < 0) {
            throw new ClientException('maxRequestsPerWindow must be 0 or greater');
        }

        $this->assertValidProxyUrl($this->proxyUrl);

        $resolvedClock = $clock ?? new SystemClock;

        $resolvedHttpClient = $httpClient ?? new GuzzleClient([
            'timeout' => $this->requestTimeoutSeconds,
            'proxy' => $this->proxyUrl,
        ]);

        $this->transport = new HttpTransport(
            httpClient: $resolvedHttpClient,
            maxRetries: $this->maxRetries,
            retryDelayMilliseconds: $this->retryDelayMilliseconds,
            defaultHeaders: $this->defaultHeaders,
            clock: $resolvedClock,
            requestDelayMilliseconds: $this->requestDelayMilliseconds,
            requestDelayJitterMilliseconds: $this->requestDelayJitterMilliseconds,
            maxRequestsPerWindow: $this->maxRequestsPerWindow,
        );

        $this->cacheStore = new CacheStore($cache, $this->cacheTtl);

        $this->searchParser = new SearchParser;
        $this->listingDetailParser = new ListingDetailParser;
    }

    public function getStats(): array
    {
        return $this->transport->getStats();
    }

    public function resetStats(): void
    {
        $this->transport->resetStats();
    }

    public function getSearch(SearchQuery $query, array $excludedCategoryIds = []): SearchResult
    {
        $cacheKey = $query->buildCacheKey();

        $cached = $this->cacheStore->fetch($cacheKey, static fn (array $data): SearchResult => SearchResult::fromArray($data));

        if ($cached instanceof SearchResult) {
            return $cached->excludeCategories($excludedCategoryIds);
        }

        $result = $this->fetchSearchResult($query);

        $this->cacheStore->store($cacheKey, $result->toArray());

        return $result->excludeCategories($excludedCategoryIds);
    }

    /**
     * Yields all listings by paginating through search results.
     *
     * Always fetches live from the API (bypasses cache) to avoid stale
     * pagination metadata causing early termination or over-iteration.
     *
     * @return Generator<int, Listing>
     */
    public function getSearchAll(SearchQuery $query, array $excludedCategoryIds = []): Generator
    {
        $offset = $query->offset;
        $yieldedIndex = 0;
        $previousItemIds = [];
        $seenItemIds = [];
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

            $filteredResult = $result->excludeCategories($excludedCategoryIds);

            $currentItemIds = array_map(fn (Listing $l): string => $l->itemId, $result->listings);

            if ($currentItemIds === $previousItemIds) {
                break;
            }

            $previousItemIds = $currentItemIds;

            foreach ($filteredResult->listings as $listing) {
                if (isset($seenItemIds[$listing->itemId])) {
                    continue;
                }

                $seenItemIds[$listing->itemId] = true;
                yield $yieldedIndex => $listing;
                $yieldedIndex++;
            }

            $offset += $currentQuery->limit;

            $safetyBoundary = $result->maxAllowedPageNumber * $currentQuery->limit;

            if ($safetyBoundary > 0 && $offset >= $safetyBoundary) {
                break;
            }
        }
    }

    public function getCategoryCatalog(int $categoryId): CategoryCatalog
    {
        $cacheKey = 'marktplaats:categories:'.$categoryId;

        $cached = $this->cacheStore->fetch($cacheKey, static fn (array $data): CategoryCatalog => CategoryCatalog::fromArray($data));

        if ($cached instanceof CategoryCatalog) {
            return $cached;
        }

        $discoveryQuery = new SearchQuery(
            categoryId: $categoryId,
            limit: 1,
        );

        $url = $discoveryQuery->buildUrl();
        $body = $this->fetchRawResponse($url);

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ClientException('Failed to decode category catalog response: '.$e->getMessage(), 0, $e);
        }

        $catalog = $this->searchParser->parseCategoryCatalog(
            $data,
            $categoryId,
        );

        $this->cacheStore->store($cacheKey, $catalog->toArray());

        return $catalog;
    }

    public function getFilterCatalog(int $categoryId, ?int $subCategoryId = null): FilterCatalog
    {
        $cacheKey = 'marktplaats:filters:'.$categoryId.':'.($subCategoryId ?? 'all');

        $cached = $this->cacheStore->fetch($cacheKey, static fn (array $data): FilterCatalog => FilterCatalog::fromArray($data));

        if ($cached instanceof FilterCatalog) {
            return $cached;
        }

        $discoveryQuery = new SearchQuery(
            categoryId: $categoryId,
            subCategoryId: $subCategoryId,
            limit: 1,
        );

        $result = $this->fetchSearchResult($discoveryQuery);

        $catalog = new FilterCatalog(
            facets: array_values($result->facets),
            categoryId: $categoryId,
            subCategoryId: $subCategoryId,
            discoveredAt: new DateTimeImmutable,
        );

        $this->cacheStore->store($cacheKey, $catalog->toArray());

        return $catalog;
    }

    public function getListing(string $url): ListingDetail
    {
        $fullUrl = $this->resolveListingUrl($url);
        $cacheKey = $this->buildListingDetailCacheKey($fullUrl);

        $cached = $this->cacheStore->fetch($cacheKey, static fn (array $data): ListingDetail => ListingDetail::fromArray($data));

        if ($cached instanceof ListingDetail) {
            return $cached;
        }

        $html = $this->fetchRawResponse($fullUrl);
        $detail = $this->listingDetailParser->parseHtml($html, $fullUrl);

        $this->cacheStore->store($cacheKey, $detail->toArray());

        return $detail;
    }

    public function resetSession(): void
    {
        $this->transport->resetSession();
    }

    private function assertValidProxyUrl(?string $proxyUrl): void
    {
        if ($proxyUrl === null) {
            return;
        }

        $scheme = parse_url($proxyUrl, PHP_URL_SCHEME);

        if (! is_string($scheme) || ! in_array($scheme, ['http', 'https', 'socks5'], true)) {
            throw new ClientException('proxyUrl must use http://, https://, or socks5://');
        }

        $host = parse_url($proxyUrl, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            throw new ClientException('proxyUrl must include a valid host');
        }
    }

    private function resolveListingUrl(string $url): string
    {
        return UrlResolver::resolveAgainstBase($url);
    }

    private function fetchSearchResult(SearchQuery $query): SearchResult
    {
        $url = $query->buildUrl();
        $body = $this->fetchRawResponse($url);

        return $this->searchParser->parseJson($body);
    }

    private function fetchRawResponse(string $url): string
    {
        return $this->transport->get($url);
    }

    private function buildListingDetailCacheKey(string $url): string
    {
        return 'marktplaats:listing:'.md5($url);
    }
}
