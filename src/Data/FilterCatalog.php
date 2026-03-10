<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

use DateTimeImmutable;
use NiekNijland\Marktplaats\Data\Enums\SearchFacetType;

readonly class FilterCatalog
{
    /**
     * @param  list<SearchFacet>  $facets
     */
    public function __construct(
        public array $facets,
        public int $categoryId,
        public ?int $subCategoryId,
        public DateTimeImmutable $discoveredAt,
    ) {}

    /**
     * @return array{facets: list<array<string, mixed>>, categoryId: int, subCategoryId: ?int, discoveredAt: string}
     */
    public function toArray(): array
    {
        return [
            'facets' => array_map(fn (SearchFacet $facet): array => $facet->toArray(), $this->facets),
            'categoryId' => $this->categoryId,
            'subCategoryId' => $this->subCategoryId,
            'discoveredAt' => $this->discoveredAt->format('c'),
        ];
    }

    /**
     * @param  array{facets?: list<array<string, mixed>>, categoryId?: int, subCategoryId?: ?int, discoveredAt?: string, l1CategoryId?: int, l2CategoryId?: ?int}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            facets: array_map(
                fn (array $facet): SearchFacet => SearchFacet::fromArray($facet),
                self::normalizeListOfArrays($data['facets'] ?? []),
            ),
            categoryId: (int) ($data['categoryId'] ?? $data['l1CategoryId'] ?? 0),
            subCategoryId: self::toNullableInt($data['subCategoryId'] ?? $data['l2CategoryId'] ?? null),
            discoveredAt: new DateTimeImmutable($data['discoveredAt'] ?? 'now'),
        );
    }

    /**
     * @return list<SearchFacet>
     */
    public function getRangeFacets(): array
    {
        return array_values(array_filter(
            $this->facets,
            fn (SearchFacet $facet): bool => $facet->isType(SearchFacetType::ATTRIBUTE_RANGE),
        ));
    }

    /**
     * @return list<SearchFacet>
     */
    public function getGroupFacets(): array
    {
        return array_values(array_filter(
            $this->facets,
            fn (SearchFacet $facet): bool => $facet->isType(SearchFacetType::ATTRIBUTE_GROUP),
        ));
    }

    public function findByKey(string $key): ?SearchFacet
    {
        foreach ($this->facets as $facet) {
            if ($facet->key === $key) {
                return $facet;
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

    private static function toNullableInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }
}
