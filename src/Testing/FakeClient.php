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
use PHPUnit\Framework\Assert;

class FakeClient implements ClientInterface
{
    /** @var RecordedCall[] */
    private array $recordedCalls = [];

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

    public function getSearch(SearchQuery $query): SearchResult
    {
        $this->recordedCalls[] = new RecordedCall('getSearch', [$query]);

        if ($this->exception instanceof ClientException) {
            throw $this->exception;
        }

        $result = array_shift($this->searchResults) ?? SearchResult::empty();

        return $this->applyExcludedCategoryFilter($result, $query->excludedCategoryIds);
    }

    /**
     * @return Generator<int, Listing>
     */
    public function getSearchAll(SearchQuery $query): Generator
    {
        $this->recordedCalls[] = new RecordedCall('getSearchAll', [$query]);

        if ($this->exception instanceof ClientException) {
            throw $this->exception;
        }

        $result = array_shift($this->searchResults) ?? SearchResult::empty();
        $result = $this->applyExcludedCategoryFilter($result, $query->excludedCategoryIds);

        foreach ($result->listings as $index => $listing) {
            yield $index => $listing;
        }
    }

    public function getCategoryCatalog(int $l1CategoryId): CategoryCatalog
    {
        $this->recordedCalls[] = new RecordedCall('getCategoryCatalog', [$l1CategoryId]);

        if ($this->exception instanceof ClientException) {
            throw $this->exception;
        }

        if (! $this->categoryCatalog instanceof CategoryCatalog) {
            throw new ClientException('No category catalog seeded in FakeClient');
        }

        return $this->categoryCatalog;
    }

    public function getFilterCatalog(int $l1CategoryId, ?int $l2CategoryId = null): FilterCatalog
    {
        $this->recordedCalls[] = new RecordedCall('getFilterCatalog', [$l1CategoryId, $l2CategoryId]);

        if ($this->exception instanceof ClientException) {
            throw $this->exception;
        }

        if (! $this->filterCatalog instanceof FilterCatalog) {
            throw new ClientException('No filter catalog seeded in FakeClient');
        }

        return $this->filterCatalog;
    }

    /**
     * @param  list<int>  $excludedCategoryIds
     */
    private function applyExcludedCategoryFilter(SearchResult $result, array $excludedCategoryIds): SearchResult
    {
        if ($excludedCategoryIds === []) {
            return $result;
        }

        $filtered = array_values(array_filter(
            $result->listings,
            fn (Listing $listing): bool => $listing->categoryId === null
                || ! in_array($listing->categoryId, $excludedCategoryIds, true),
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

    public function getListing(string $url): ListingDetail
    {
        $this->recordedCalls[] = new RecordedCall('getListing', [$url]);

        if ($this->exception instanceof ClientException) {
            throw $this->exception;
        }

        if ($this->listingDetails === []) {
            throw new ClientException('No listing detail seeded in FakeClient');
        }

        return array_shift($this->listingDetails);
    }

    public function resetSession(): void
    {
        $this->recordedCalls[] = new RecordedCall('resetSession', []);
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
        $calls = array_filter(
            $this->recordedCalls,
            fn (RecordedCall $call): bool => $call->method === $method,
        );

        Assert::assertNotEmpty($calls, "Expected method [{$method}] to have been called, but it was not.");
    }

    public function assertNotCalled(string $method): void
    {
        $calls = array_filter(
            $this->recordedCalls,
            fn (RecordedCall $call): bool => $call->method === $method,
        );

        Assert::assertEmpty($calls, "Expected method [{$method}] to not have been called, but it was.");
    }

    public function assertCalledTimes(string $method, int $times): void
    {
        $calls = array_filter(
            $this->recordedCalls,
            fn (RecordedCall $call): bool => $call->method === $method,
        );

        $actual = count($calls);

        Assert::assertSame(
            $times,
            $actual,
            "Expected method [{$method}] to have been called {$times} time(s), but it was called {$actual} time(s).",
        );
    }
}
