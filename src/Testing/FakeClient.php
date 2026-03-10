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

    public function getSearch(SearchQuery $query, array $excludedCategoryIds = []): SearchResult
    {
        $this->recordedCalls[] = new RecordedCall('getSearch', [$query, $excludedCategoryIds]);

        if ($this->exception instanceof ClientException) {
            throw $this->exception;
        }

        $result = array_shift($this->searchResults) ?? SearchResult::empty();

        return $result->excludeCategories($excludedCategoryIds);
    }

    /**
     * @return Generator<int, Listing>
     */
    public function getSearchAll(SearchQuery $query, array $excludedCategoryIds = []): Generator
    {
        $this->recordedCalls[] = new RecordedCall('getSearchAll', [$query, $excludedCategoryIds]);

        if ($this->exception instanceof ClientException) {
            throw $this->exception;
        }

        $result = array_shift($this->searchResults) ?? SearchResult::empty();
        $result = $result->excludeCategories($excludedCategoryIds);

        foreach ($result->listings as $index => $listing) {
            yield $index => $listing;
        }
    }

    public function getCategoryCatalog(int $categoryId): CategoryCatalog
    {
        $this->recordedCalls[] = new RecordedCall('getCategoryCatalog', [$categoryId]);

        if ($this->exception instanceof ClientException) {
            throw $this->exception;
        }

        if (! $this->categoryCatalog instanceof CategoryCatalog) {
            throw new ClientException('No category catalog seeded in FakeClient');
        }

        return $this->categoryCatalog;
    }

    public function getFilterCatalog(int $categoryId, ?int $subCategoryId = null): FilterCatalog
    {
        $this->recordedCalls[] = new RecordedCall('getFilterCatalog', [$categoryId, $subCategoryId]);

        if ($this->exception instanceof ClientException) {
            throw $this->exception;
        }

        if (! $this->filterCatalog instanceof FilterCatalog) {
            throw new ClientException('No filter catalog seeded in FakeClient');
        }

        return $this->filterCatalog;
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
