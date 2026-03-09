<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Testing;

use Generator;
use NiekNijland\Marktplaats\ClientInterface;
use NiekNijland\Marktplaats\Data\Listing;
use NiekNijland\Marktplaats\Data\ListingDetail;
use NiekNijland\Marktplaats\Data\MotorcycleBrandCatalog;
use NiekNijland\Marktplaats\Data\MotorcycleSearchQuery;
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

    private ?MotorcycleBrandCatalog $brandCatalog = null;

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

    public function seedBrandCatalog(MotorcycleBrandCatalog $catalog): self
    {
        $this->brandCatalog = $catalog;

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

        return array_shift($this->searchResults) ?? SearchResult::empty();
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

        foreach ($result->listings as $index => $listing) {
            yield $index => $listing;
        }
    }

    public function getMotorcycleSearch(MotorcycleSearchQuery $query): SearchResult
    {
        $this->recordedCalls[] = new RecordedCall('getMotorcycleSearch', [$query]);

        if ($this->exception instanceof ClientException) {
            throw $this->exception;
        }

        $result = array_shift($this->searchResults) ?? SearchResult::empty();

        if (! $query->strictMode) {
            return $result;
        }

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

    public function getMotorcycleBrandCatalog(): MotorcycleBrandCatalog
    {
        $this->recordedCalls[] = new RecordedCall('getMotorcycleBrandCatalog', []);

        if ($this->exception instanceof ClientException) {
            throw $this->exception;
        }

        if (! $this->brandCatalog instanceof MotorcycleBrandCatalog) {
            throw new ClientException('No brand catalog seeded in FakeClient');
        }

        return $this->brandCatalog;
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
