<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

use DateTimeImmutable;

readonly class CategoryCatalog
{
    /**
     * @param  list<Category>  $categories
     */
    public function __construct(
        public array $categories,
        public int $parentCategoryId,
        public DateTimeImmutable $discoveredAt,
    ) {}

    /**
     * @return array{categories: list<array{id: int, key: ?string, name: ?string, fullName: ?string, parentId: ?int, parentKey: ?string}>, parentCategoryId: int, discoveredAt: string}
     */
    public function toArray(): array
    {
        return [
            'categories' => array_map(fn (Category $category): array => $category->toArray(), $this->categories),
            'parentCategoryId' => $this->parentCategoryId,
            'discoveredAt' => $this->discoveredAt->format('c'),
        ];
    }

    /**
     * @param  array{categories?: list<array{id: int, key?: ?string, name?: ?string, fullName?: ?string, parentId?: ?int, parentKey?: ?string}>, parentCategoryId?: int, discoveredAt?: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            categories: array_map(
                fn (array $category): Category => Category::fromArray($category),
                self::normalizeListOfArrays($data['categories'] ?? []),
            ),
            parentCategoryId: isset($data['parentCategoryId']) ? (int) $data['parentCategoryId'] : 0,
            discoveredAt: new DateTimeImmutable($data['discoveredAt'] ?? 'now'),
        );
    }

    public function findById(int $id): ?Category
    {
        foreach ($this->categories as $category) {
            if ($category->id === $id) {
                return $category;
            }
        }

        return null;
    }

    public function findByKey(string $key): ?Category
    {
        foreach ($this->categories as $category) {
            if ($category->key === $key) {
                return $category;
            }
        }

        return null;
    }

    public function findByName(string $name): ?Category
    {
        foreach ($this->categories as $category) {
            if ($category->name === $name) {
                return $category;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function normalizeListOfArrays(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn (mixed $item): bool => is_array($item),
        ));
    }
}
