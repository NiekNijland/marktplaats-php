<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class SearchRequestSortOptions
{
    public function __construct(
        public ?string $sortBy,
        public ?string $sortOrder,
    ) {}

    /**
     * @return array{sortBy: ?string, sortOrder: ?string}
     */
    public function toArray(): array
    {
        return [
            'sortBy' => $this->sortBy,
            'sortOrder' => $this->sortOrder,
        ];
    }

    /**
     * @param  array{sortBy?: ?string, sortOrder?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sortBy: $data['sortBy'] ?? null,
            sortOrder: $data['sortOrder'] ?? null,
        );
    }
}
