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

    /**
     * @param  list<AttributeRange>  $attributeRanges
     * @param  list<int>  $attributesById
     * @param  list<AttributeByKey>  $attributesByKey
     */
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
        public ?string $postcode = null,
        public array $attributeRanges = [],
        public array $attributesById = [],
        public array $attributesByKey = [],
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
            postcode: $this->postcode,
            attributeRanges: $this->attributeRanges,
            attributesById: $this->attributesById,
            attributesByKey: $this->attributesByKey,
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

        if ($this->postcode !== null) {
            $params['postcode'] = $this->postcode;
        }

        return $params;
    }

    /**
     * @return array<string, list<string>>
     */
    public function toArrayQueryParams(): array
    {
        $params = [];

        if ($this->attributeRanges !== []) {
            $params['attributeRanges'] = array_map(
                fn (AttributeRange $range): string => $range->toString(),
                $this->attributeRanges,
            );
        }

        if ($this->attributesById !== []) {
            $params['attributesById'] = array_map(
                fn (int $id): string => (string) $id,
                $this->attributesById,
            );
        }

        if ($this->attributesByKey !== []) {
            $params['attributesByKey'] = array_map(
                fn (AttributeByKey $attr): string => $attr->toString(),
                $this->attributesByKey,
            );
        }

        return $params;
    }

    public function buildUrl(): string
    {
        $queryString = http_build_query($this->toQueryParams());

        foreach ($this->toArrayQueryParams() as $key => $values) {
            $encodedKey = urlencode($key).'%5B%5D';

            foreach ($values as $value) {
                if ($queryString !== '') {
                    $queryString .= '&';
                }

                $queryString .= $encodedKey.'='.urlencode($value);
            }
        }

        return self::BASE_URL.'?'.$queryString;
    }

    public function buildCacheKey(): string
    {
        $params = $this->toQueryParams();

        foreach ($this->toArrayQueryParams() as $key => $values) {
            sort($values);
            $params[$key] = implode(',', $values);
        }

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
