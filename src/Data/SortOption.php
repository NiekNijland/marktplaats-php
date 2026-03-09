<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

use NiekNijland\Marktplaats\Data\Enums\SortBy;
use NiekNijland\Marktplaats\Data\Enums\SortOrder;

readonly class SortOption
{
    public function __construct(
        public ?SortBy $sortBy,
        public ?SortOrder $sortOrder,
    ) {}

    /**
     * @return array{sortBy: ?string, sortOrder: ?string}
     */
    public function toArray(): array
    {
        return [
            'sortBy' => $this->sortBy?->value,
            'sortOrder' => $this->sortOrder?->value,
        ];
    }

    /**
     * @param  array{sortBy?: ?string, sortOrder?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sortBy: isset($data['sortBy']) ? SortBy::tryFrom($data['sortBy']) : null,
            sortOrder: isset($data['sortOrder']) ? SortOrder::tryFrom($data['sortOrder']) : null,
        );
    }
}
