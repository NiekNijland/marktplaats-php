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
        $originalRequest = is_array($data['originalRequest'] ?? null) ? $data['originalRequest'] : null;

        return new self(
            searchQuery: is_string($data['searchQuery'] ?? null) ? $data['searchQuery'] : null,
            categories: SearchRequestCategories::fromArray(is_array($data['categories'] ?? null) ? $data['categories'] : []),
            sortOptions: SearchRequestSortOptions::fromArray(is_array($data['sortOptions'] ?? null) ? $data['sortOptions'] : []),
            pagination: SearchRequestPagination::fromArray(is_array($data['pagination'] ?? null) ? $data['pagination'] : []),
            viewOptions: SearchRequestViewOptions::fromArray(is_array($data['viewOptions'] ?? null) ? $data['viewOptions'] : []),
            originalRequest: ! $isNested && $originalRequest !== null
                ? self::fromArray($originalRequest, isNested: true)
                : null,
        );
    }
}
