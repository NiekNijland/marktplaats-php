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
        public int $l1CategoryId,
        public ?int $l2CategoryId,
        public DateTimeImmutable $discoveredAt,
    ) {}

    /**
     * @return array{facets: list<array<string, mixed>>, l1CategoryId: int, l2CategoryId: ?int, discoveredAt: string}
     */
    public function toArray(): array
    {
        return [
            'facets' => array_map(fn (SearchFacet $facet): array => $facet->toArray(), $this->facets),
            'l1CategoryId' => $this->l1CategoryId,
            'l2CategoryId' => $this->l2CategoryId,
            'discoveredAt' => $this->discoveredAt->format('c'),
        ];
    }

    /**
     * @param  array{facets?: list<array<string, mixed>>, l1CategoryId?: int, l2CategoryId?: ?int, discoveredAt?: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            facets: array_map(
                fn (array $facet): SearchFacet => SearchFacet::fromArray($facet),
                $data['facets'] ?? [],
            ),
            l1CategoryId: (int) ($data['l1CategoryId'] ?? 0),
            l2CategoryId: isset($data['l2CategoryId']) ? $data['l2CategoryId'] : null,
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
