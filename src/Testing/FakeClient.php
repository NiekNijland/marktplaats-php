<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Testing;

use Generator;
use NiekNijland\Marktplaats\ClientInterface;
use NiekNijland\Marktplaats\Data\CategoryCatalog;
use NiekNijland\Marktplaats\Data\FilterCatalog;
use NiekNijland\Marktplaats\Data\Listing;
use NiekNijland\Marktplaats\Data\ListingDetail;
use NiekNijland\Marktplaats\Data\SearchQuery;
use NiekNijland\Marktplaats\Data\SearchResult;
use NiekNijland\Marktplaats\Exception\ClientException;
use RuntimeException;

class FakeClient implements ClientInterface
{
    /** @var RecordedCall[] */
    private array $recordedCalls = [];

    /**
     * @var array{
     *     requests: int,
     *     successes: int,
     *     failures: int,
     *     retries: int,
     *     session_resets: int,
     *     last_request_at: ?float,
     *     total_sleep_ms: float,
     * }
     */
    private array $stats = [
        'requests' => 0,
        'successes' => 0,
        'failures' => 0,
        'retries' => 0,
        'session_resets' => 0,
        'last_request_at' => null,
        'total_sleep_ms' => 0.0,
    ];

    /** @var SearchResult[] */
    private array $searchResults = [];

    /** @var ListingDetail[] */
    private array $listingDetails = [];

    private ?CategoryCatalog $categoryCatalog = null;

    private ?FilterCatalog $filterCatalog = null;

    private ?ClientException $exception = null;

    public function seedSearchResult(SearchResult $result): self
    {
        $this->searchResults[] = $result;

        return $this;
    }

    public function seedListingDetail(ListingDetail $detail): self
    {
        $this->listingDetails[] = $detail;

        return $this;
    }

    public function seedCategoryCatalog(CategoryCatalog $catalog): self
    {
        $this->categoryCatalog = $catalog;

        return $this;
    }

    public function seedFilterCatalog(FilterCatalog $catalog): self
    {
        $this->filterCatalog = $catalog;

        return $this;
    }

    public function shouldThrow(ClientException $exception): self
    {
        $this->exception = $exception;

        return $this;
    }

    /**
     * @param  array{
     *     requests?: int,
     *     successes?: int,
     *     failures?: int,
     *     retries?: int,
     *     session_resets?: int,
     *     last_request_at?: ?float,
     *     total_sleep_ms?: float,
     * }  $stats
     */
    public function seedStats(array $stats): self
    {
        $this->stats = array_replace($this->stats, $stats);

        return $this;
    }

    public function getStats(): array
    {
        $this->recordedCalls[] = new RecordedCall('getStats', []);

        return $this->stats;
    }

    public function resetStats(): void
    {
        $this->recordedCalls[] = new RecordedCall('resetStats', []);
        $this->stats = [
            'requests' => 0,
            'successes' => 0,
            'failures' => 0,
            'retries' => 0,
            'session_resets' => 0,
            'last_request_at' => null,
            'total_sleep_ms' => 0.0,
        ];
    }

    public function getSearch(SearchQuery $query, array $excludedCategoryIds = []): SearchResult
    {
        $this->recordedCalls[] = new RecordedCall('getSearch', [$query, $excludedCategoryIds]);
        $this->recordRequest();

        if ($this->exception instanceof ClientException) {
            $this->stats['failures']++;
            throw $this->exception;
        }

        $result = array_shift($this->searchResults) ?? SearchResult::empty();
        $this->stats['successes']++;

        return $result->excludeCategories($excludedCategoryIds);
    }

    /**
     * @return Generator<int, Listing>
     */
    public function getSearchAll(SearchQuery $query, array $excludedCategoryIds = []): Generator
    {
        $this->recordedCalls[] = new RecordedCall('getSearchAll', [$query, $excludedCategoryIds]);
        $this->recordRequest();

        if ($this->exception instanceof ClientException) {
            $this->stats['failures']++;
            throw $this->exception;
        }

        $result = array_shift($this->searchResults) ?? SearchResult::empty();
        $result = $result->excludeCategories($excludedCategoryIds);
        $this->stats['successes']++;

        foreach ($result->listings as $index => $listing) {
            yield $index => $listing;
        }
    }

    public function getCategoryCatalog(int $categoryId): CategoryCatalog
    {
        $this->recordedCalls[] = new RecordedCall('getCategoryCatalog', [$categoryId]);
        $this->recordRequest();

        if ($this->exception instanceof ClientException) {
            $this->stats['failures']++;
            throw $this->exception;
        }

        if (! $this->categoryCatalog instanceof CategoryCatalog) {
            $this->stats['failures']++;
            throw new ClientException('No category catalog seeded in FakeClient');
        }

        $this->stats['successes']++;

        return $this->categoryCatalog;
    }

    public function getFilterCatalog(int $categoryId, ?int $subCategoryId = null): FilterCatalog
    {
        $this->recordedCalls[] = new RecordedCall('getFilterCatalog', [$categoryId, $subCategoryId]);
        $this->recordRequest();

        if ($this->exception instanceof ClientException) {
            $this->stats['failures']++;
            throw $this->exception;
        }

        if (! $this->filterCatalog instanceof FilterCatalog) {
            $this->stats['failures']++;
            throw new ClientException('No filter catalog seeded in FakeClient');
        }

        $this->stats['successes']++;

        return $this->filterCatalog;
    }

    public function getListing(string $url): ListingDetail
    {
        $this->recordedCalls[] = new RecordedCall('getListing', [$url]);
        $this->recordRequest();

        if ($this->exception instanceof ClientException) {
            $this->stats['failures']++;
            throw $this->exception;
        }

        if ($this->listingDetails === []) {
            $this->stats['failures']++;
            throw new ClientException('No listing detail seeded in FakeClient');
        }

        $this->stats['successes']++;

        return array_shift($this->listingDetails);
    }

    public function resetSession(): void
    {
        $this->recordedCalls[] = new RecordedCall('resetSession', []);
        $this->stats['session_resets']++;
    }

    /**
     * @return RecordedCall[]
     */
    public function getRecordedCalls(): array
    {
        return $this->recordedCalls;
    }

    public function assertCalled(string $method): void
    {
        if ($this->countCalls($method) < 1) {
            throw new RuntimeException("Expected method [{$method}] to have been called, but it was not.");
        }
    }

    public function assertNotCalled(string $method): void
    {
        if ($this->countCalls($method) > 0) {
            throw new RuntimeException("Expected method [{$method}] to not have been called, but it was.");
        }
    }

    public function assertCalledTimes(string $method, int $times): void
    {
        $actual = $this->countCalls($method);

        if ($actual !== $times) {
            throw new RuntimeException(
                "Expected method [{$method}] to have been called {$times} time(s), but it was called {$actual} time(s).",
            );
        }
    }

    private function countCalls(string $method): int
    {
        return count(array_filter(
            $this->recordedCalls,
            fn (RecordedCall $call): bool => $call->method === $method,
        ));
    }

    private function recordRequest(): void
    {
        $this->stats['requests']++;
        $this->stats['last_request_at'] = microtime(true);
    }
}
