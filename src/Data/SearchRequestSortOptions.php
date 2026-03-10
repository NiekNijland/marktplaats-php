<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

use NiekNijland\Marktplaats\Data\Enums\SortBy;
use NiekNijland\Marktplaats\Data\Enums\SortOrder;

readonly class SearchRequestSortOptions
{
    public function __construct(
        public ?SortBy $sortBy,
        public ?SortOrder $sortOrder,
        public ?string $rawSortBy = null,
        public ?string $rawSortOrder = null,
    ) {}

    /**
     * @return array{sortBy: ?string, sortOrder: ?string}
     */
    public function toArray(): array
    {
        return [
            'sortBy' => $this->rawSortBy ?? $this->sortBy?->value,
            'sortOrder' => $this->rawSortOrder ?? $this->sortOrder?->value,
        ];
    }

    /**
     * @param  array{sortBy?: ?string, sortOrder?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        $rawSortBy = is_string($data['sortBy'] ?? null) ? $data['sortBy'] : null;
        $rawSortOrder = is_string($data['sortOrder'] ?? null) ? $data['sortOrder'] : null;

        return new self(
            sortBy: $rawSortBy !== null ? SortBy::tryFrom($rawSortBy) : null,
            sortOrder: $rawSortOrder !== null ? SortOrder::tryFrom($rawSortOrder) : null,
            rawSortBy: $rawSortBy,
            rawSortOrder: $rawSortOrder,
        );
    }
}
