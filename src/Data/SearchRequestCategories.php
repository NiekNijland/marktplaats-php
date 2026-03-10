<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class SearchRequestCategories
{
    public function __construct(
        public ?Category $category,
        public ?Category $subCategory,
    ) {}

    /**
     * @return array{l1CategoryId: ?int, l1CategoryKey: ?string, l1CategoryFullName: ?string, l2CategoryId: ?int, l2CategoryKey: ?string, l2CategoryFullName: ?string}
     */
    public function toArray(): array
    {
        return [
            'l1CategoryId' => $this->category?->id,
            'l1CategoryKey' => $this->category?->key,
            'l1CategoryFullName' => $this->category?->fullName,
            'l2CategoryId' => $this->subCategory?->id,
            'l2CategoryKey' => $this->subCategory?->key,
            'l2CategoryFullName' => $this->subCategory?->fullName,
        ];
    }

    /**
     * @param  array{l1CategoryId?: ?int, l1CategoryKey?: ?string, l1CategoryFullName?: ?string, l2CategoryId?: ?int, l2CategoryKey?: ?string, l2CategoryFullName?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        $l1CategoryId = isset($data['l1CategoryId']) ? (int) $data['l1CategoryId'] : null;
        $l1CategoryKey = is_string($data['l1CategoryKey'] ?? null) ? $data['l1CategoryKey'] : null;
        $l1CategoryFullName = is_string($data['l1CategoryFullName'] ?? null) ? $data['l1CategoryFullName'] : null;

        $l2CategoryId = isset($data['l2CategoryId']) ? (int) $data['l2CategoryId'] : null;
        $l2CategoryKey = is_string($data['l2CategoryKey'] ?? null) ? $data['l2CategoryKey'] : null;
        $l2CategoryFullName = is_string($data['l2CategoryFullName'] ?? null) ? $data['l2CategoryFullName'] : null;

        return new self(
            category: self::makeCategory(
                id: $l1CategoryId,
                key: $l1CategoryKey,
                fullName: $l1CategoryFullName,
                parentId: null,
                parentKey: null,
            ),
            subCategory: self::makeCategory(
                id: $l2CategoryId,
                key: $l2CategoryKey,
                fullName: $l2CategoryFullName,
                parentId: $l1CategoryId,
                parentKey: $l1CategoryKey,
            ),
        );
    }

    private static function makeCategory(
        ?int $id,
        ?string $key,
        ?string $fullName,
        ?int $parentId,
        ?string $parentKey,
    ): ?Category {
        if ($id === null && $key === null && $fullName === null) {
            return null;
        }

        return new Category(
            id: $id ?? 0,
            key: $key,
            name: null,
            fullName: $fullName,
            parentId: $parentId,
            parentKey: $parentKey,
        );
    }
}
