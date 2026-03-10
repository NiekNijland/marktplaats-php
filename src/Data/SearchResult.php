<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class SearchResult
{
    /**
     * @param  Listing[]  $listings
     * @param  Listing[]  $topBlock
     * @param  SearchFacet[]  $facets
     * @param  SortOption[]  $sortOptions
     * @param  Category[]  $searchCategoryOptions
     */
    public function __construct(
        public array $listings,
        public array $topBlock,
        public array $facets,
        public int $totalResultCount,
        public int $maxAllowedPageNumber,
        public ?string $correlationId,
        public ?string $originalQuery,
        public array $sortOptions,
        public ?int $searchCategory,
        public array $searchCategoryOptions,
        public ?SearchRequest $searchRequest,
        public ?SearchMetaTags $metaTags,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'listings' => array_map(fn (Listing $l): array => $l->toArray(), $this->listings),
            'topBlock' => array_map(fn (Listing $l): array => $l->toArray(), $this->topBlock),
            'facets' => array_map(fn (SearchFacet $f): array => $f->toArray(), $this->facets),
            'totalResultCount' => $this->totalResultCount,
            'maxAllowedPageNumber' => $this->maxAllowedPageNumber,
            'correlationId' => $this->correlationId,
            'originalQuery' => $this->originalQuery,
            'sortOptions' => array_map(fn (SortOption $s): array => $s->toArray(), $this->sortOptions),
            'searchCategory' => $this->searchCategory,
            'searchCategoryOptions' => array_map(fn (Category $o): array => $o->toArray(), $this->searchCategoryOptions),
            'searchRequest' => $this->searchRequest?->toArray(),
            'metaTags' => $this->metaTags?->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            listings: array_map(
                fn (array $l): Listing => Listing::fromArray($l),
                $data['listings'] ?? [],
            ),
            topBlock: array_map(
                fn (array $l): Listing => Listing::fromArray($l),
                $data['topBlock'] ?? [],
            ),
            facets: array_map(
                fn (array $f): SearchFacet => SearchFacet::fromArray($f),
                $data['facets'] ?? [],
            ),
            totalResultCount: $data['totalResultCount'] ?? 0,
            maxAllowedPageNumber: $data['maxAllowedPageNumber'] ?? 0,
            correlationId: $data['correlationId'] ?? null,
            originalQuery: $data['originalQuery'] ?? null,
            sortOptions: array_map(
                fn (array $s): SortOption => SortOption::fromArray($s),
                $data['sortOptions'] ?? [],
            ),
            searchCategory: $data['searchCategory'] ?? null,
            searchCategoryOptions: array_map(
                fn (array $o): Category => Category::fromArray(['id' => (int) ($o['id'] ?? 0)] + $o),
                $data['searchCategoryOptions'] ?? [],
            ),
            searchRequest: isset($data['searchRequest']) ? SearchRequest::fromArray($data['searchRequest']) : null,
            metaTags: isset($data['metaTags']) ? SearchMetaTags::fromArray($data['metaTags']) : null,
        );
    }

    /**
     * @param  list<Listing>  $listings
     */
    public function withListings(array $listings): self
    {
        return new self(
            listings: $listings,
            topBlock: $this->topBlock,
            facets: $this->facets,
            totalResultCount: $this->totalResultCount,
            maxAllowedPageNumber: $this->maxAllowedPageNumber,
            correlationId: $this->correlationId,
            originalQuery: $this->originalQuery,
            sortOptions: $this->sortOptions,
            searchCategory: $this->searchCategory,
            searchCategoryOptions: $this->searchCategoryOptions,
            searchRequest: $this->searchRequest,
            metaTags: $this->metaTags,
        );
    }

    /**
     * @param  list<int>  $excludedCategoryIds
     */
    public function excludeCategories(array $excludedCategoryIds): self
    {
        if ($excludedCategoryIds === []) {
            return $this;
        }

        $filter = static fn (Listing $listing): bool => $listing->categoryId === null
            || ! in_array($listing->categoryId, $excludedCategoryIds, true);

        return new self(
            listings: array_values(array_filter($this->listings, $filter)),
            topBlock: array_values(array_filter($this->topBlock, $filter)),
            facets: $this->facets,
            totalResultCount: $this->totalResultCount,
            maxAllowedPageNumber: $this->maxAllowedPageNumber,
            correlationId: $this->correlationId,
            originalQuery: $this->originalQuery,
            sortOptions: $this->sortOptions,
            searchCategory: $this->searchCategory,
            searchCategoryOptions: $this->searchCategoryOptions,
            searchRequest: $this->searchRequest,
            metaTags: $this->metaTags,
        );
    }

    public static function empty(): self
    {
        return new self(
            listings: [],
            topBlock: [],
            facets: [],
            totalResultCount: 0,
            maxAllowedPageNumber: 0,
            correlationId: null,
            originalQuery: null,
            sortOptions: [],
            searchCategory: null,
            searchCategoryOptions: [],
            searchRequest: null,
            metaTags: null,
        );
    }
}
