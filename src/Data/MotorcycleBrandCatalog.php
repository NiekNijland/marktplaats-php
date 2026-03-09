<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

use DateTimeImmutable;

readonly class MotorcycleBrandCatalog
{
    /**
     * @param  MotorcycleBrand[]  $brands
     */
    public function __construct(
        public array $brands,
        public int $sourceCategoryId,
        public DateTimeImmutable $discoveredAt,
    ) {}

    /**
     * @return array{brands: array<int, array{categoryId: int, key: string, name: string, fullName: string, parentCategoryId: int}>, sourceCategoryId: int, discoveredAt: string}
     */
    public function toArray(): array
    {
        return [
            'brands' => array_map(fn (MotorcycleBrand $b) => $b->toArray(), $this->brands),
            'sourceCategoryId' => $this->sourceCategoryId,
            'discoveredAt' => $this->discoveredAt->format('c'),
        ];
    }

    /**
     * @param  array{brands?: list<array{categoryId: int, key: string, name: string, fullName: string, parentCategoryId: int}>, sourceCategoryId?: int, discoveredAt?: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            brands: array_map(
                fn (array $b) => MotorcycleBrand::fromArray($b),
                $data['brands'] ?? [],
            ),
            sourceCategoryId: $data['sourceCategoryId'] ?? 678,
            discoveredAt: new DateTimeImmutable($data['discoveredAt'] ?? 'now'),
        );
    }
}
