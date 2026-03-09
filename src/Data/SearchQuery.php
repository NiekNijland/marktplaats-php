<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

use NiekNijland\Marktplaats\Data\Enums\SortBy;
use NiekNijland\Marktplaats\Data\Enums\SortOrder;
use NiekNijland\Marktplaats\Data\Enums\ViewOptionKind;
use NiekNijland\Marktplaats\Exception\ClientException;

/** @phpstan-consistent-constructor */
readonly class SearchQuery
{
    private const string BASE_URL = 'https://www.marktplaats.nl/lrp/api/search';

    public function __construct(
        public ?string $query = null,
        public ?int $l1CategoryId = null,
        public ?int $l2CategoryId = null,
        public int $limit = 100,
        public int $offset = 0,
        public SortBy $sortBy = SortBy::SORT_INDEX,
        public SortOrder $sortOrder = SortOrder::DECREASING,
        public bool $searchInTitleAndDescription = true,
        public ViewOptionKind $viewOptions = ViewOptionKind::GALLERY_VIEW,
    ) {
        $this->validate();
    }

    public function withOffset(int $offset): static
    {
        return new static(
            query: $this->query,
            l1CategoryId: $this->l1CategoryId,
            l2CategoryId: $this->l2CategoryId,
            limit: $this->limit,
            offset: $offset,
            sortBy: $this->sortBy,
            sortOrder: $this->sortOrder,
            searchInTitleAndDescription: $this->searchInTitleAndDescription,
            viewOptions: $this->viewOptions,
        );
    }

    /**
     * @return array<string, string|int>
     */
    public function toQueryParams(): array
    {
        $params = [];

        if ($this->query !== null) {
            $params['query'] = $this->query;
        }

        if ($this->l1CategoryId !== null) {
            $params['l1CategoryId'] = $this->l1CategoryId;
        }

        if ($this->l2CategoryId !== null) {
            $params['l2CategoryId'] = $this->l2CategoryId;
        }

        $params['limit'] = $this->limit;
        $params['offset'] = $this->offset;
        $params['sortBy'] = $this->sortBy->value;
        $params['sortOrder'] = $this->sortOrder->value;
        $params['searchInTitleAndDescription'] = $this->searchInTitleAndDescription ? 'true' : 'false';
        $params['viewOptions'] = $this->viewOptions->value;

        return $params;
    }

    public function buildUrl(): string
    {
        return self::BASE_URL.'?'.http_build_query($this->toQueryParams());
    }

    public function buildCacheKey(): string
    {
        $params = $this->toQueryParams();
        ksort($params);

        $normalized = [];
        foreach ($params as $key => $value) {
            $normalized[$key] = (string) $value;
        }

        return 'marktplaats:search:'.sha1(http_build_query($normalized));
    }

    protected function validate(): void
    {
        if ($this->limit < 1) {
            throw new ClientException('Search limit must be at least 1, got '.$this->limit);
        }

        if ($this->limit > 100) {
            throw new ClientException('Search limit must not exceed 100, got '.$this->limit);
        }

        if ($this->offset < 0) {
            throw new ClientException('Search offset must not be negative, got '.$this->offset);
        }

        if ($this->l2CategoryId !== null && $this->l1CategoryId === null) {
            throw new ClientException('l2CategoryId requires l1CategoryId to be set');
        }
    }
}
