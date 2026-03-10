<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

use DateTimeImmutable;

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
                $data['facets'] ?? [],
            ),
            categoryId: (int) ($data['categoryId'] ?? $data['l1CategoryId'] ?? 0),
            subCategoryId: isset($data['subCategoryId']) ? $data['subCategoryId'] : ($data['l2CategoryId'] ?? null),
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
            fn (SearchFacet $facet): bool => $facet->type === 'AttributeRangeFacet',
        ));
    }

    /**
     * @return list<SearchFacet>
     */
    public function getGroupFacets(): array
    {
        return array_values(array_filter(
            $this->facets,
            fn (SearchFacet $facet): bool => $facet->type === 'AttributeGroupFacet',
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
}
