<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class SearchRequest
{
    public function __construct(
        public ?string $searchQuery,
        public SearchRequestCategories $categories,
        public SearchRequestSortOptions $sortOptions,
        public SearchRequestPagination $pagination,
        public SearchRequestViewOptions $viewOptions,
        public ?self $originalRequest,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'searchQuery' => $this->searchQuery,
            'categories' => $this->categories->toArray(),
            'sortOptions' => $this->sortOptions->toArray(),
            'pagination' => $this->pagination->toArray(),
            'viewOptions' => $this->viewOptions->toArray(),
            'originalRequest' => $this->originalRequest?->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, bool $isNested = false): self
    {
        return new self(
            searchQuery: $data['searchQuery'] ?? null,
            categories: SearchRequestCategories::fromArray($data['categories'] ?? []),
            sortOptions: SearchRequestSortOptions::fromArray($data['sortOptions'] ?? []),
            pagination: SearchRequestPagination::fromArray($data['pagination'] ?? []),
            viewOptions: SearchRequestViewOptions::fromArray($data['viewOptions'] ?? []),
            originalRequest: ! $isNested && isset($data['originalRequest'])
                ? self::fromArray($data['originalRequest'], isNested: true)
                : null,
        );
    }
}
