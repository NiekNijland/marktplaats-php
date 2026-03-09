<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

use NiekNijland\Marktplaats\Data\Enums\SortBy;
use NiekNijland\Marktplaats\Data\Enums\SortOrder;
use NiekNijland\Marktplaats\Data\Enums\ViewOptionKind;
use NiekNijland\Marktplaats\Exception\ClientException;

readonly class MotorcycleSearchQuery extends SearchQuery
{
    public const int MOTORCYCLE_ROOT_CATEGORY = 678;

    /**
     * Category IDs excluded from strict mode (non-bike categories under motorcycle tree).
     *
     * @var int[]
     */
    public const array STRICT_MODE_EXCLUDED_CATEGORIES = [723, 724];

    public function __construct(
        ?string $query = null,
        public ?MotorcycleBrand $brand = null,
        int $limit = 100,
        int $offset = 0,
        SortBy $sortBy = SortBy::SORT_INDEX,
        SortOrder $sortOrder = SortOrder::DECREASING,
        bool $searchInTitleAndDescription = true,
        ViewOptionKind $viewOptions = ViewOptionKind::GALLERY_VIEW,
        public bool $strictMode = true,
    ) {
        parent::__construct(
            query: $query,
            l1CategoryId: self::MOTORCYCLE_ROOT_CATEGORY,
            l2CategoryId: $brand?->categoryId,
            limit: $limit,
            offset: $offset,
            sortBy: $sortBy,
            sortOrder: $sortOrder,
            searchInTitleAndDescription: $searchInTitleAndDescription,
            viewOptions: $viewOptions,
        );
    }

    public function withOffset(int $offset): static
    {
        return new static(
            query: $this->query,
            brand: $this->brand,
            limit: $this->limit,
            offset: $offset,
            sortBy: $this->sortBy,
            sortOrder: $this->sortOrder,
            searchInTitleAndDescription: $this->searchInTitleAndDescription,
            viewOptions: $this->viewOptions,
            strictMode: $this->strictMode,
        );
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
    }
}
