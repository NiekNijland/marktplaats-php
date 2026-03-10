<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

use NiekNijland\Marktplaats\Data\Enums\SearchFacetType;

readonly class SearchFacet
{
    /**
     * @param  SearchFacetCategory[]  $categories
     * @param  SearchFacetAttributeGroupOption[]  $attributeGroup
     */
    public function __construct(
        public ?int $id,
        public ?string $key,
        public ?SearchFacetType $type,
        public ?string $rawType,
        public ?string $label,
        public ?bool $singleSelect,
        public ?int $categoryId,
        public array $categories,
        public array $attributeGroup,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'type' => $this->rawType ?? $this->type?->value,
            'label' => $this->label,
            'singleSelect' => $this->singleSelect,
            'categoryId' => $this->categoryId,
            'categories' => array_map(fn (SearchFacetCategory $c): array => $c->toArray(), $this->categories),
            'attributeGroup' => array_map(fn (SearchFacetAttributeGroupOption $o): array => $o->toArray(), $this->attributeGroup),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $rawType = is_string($data['type'] ?? null) ? $data['type'] : null;

        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            key: is_string($data['key'] ?? null) ? $data['key'] : null,
            type: $rawType !== null ? SearchFacetType::tryFrom($rawType) : null,
            rawType: $rawType,
            label: is_string($data['label'] ?? null) ? $data['label'] : null,
            singleSelect: is_bool($data['singleSelect'] ?? null) ? $data['singleSelect'] : null,
            categoryId: isset($data['categoryId']) ? (int) $data['categoryId'] : null,
            categories: array_map(
                fn (array $c): SearchFacetCategory => SearchFacetCategory::fromArray($c),
                self::normalizeListOfArrays($data['categories'] ?? []),
            ),
            attributeGroup: array_map(
                fn (array $o): SearchFacetAttributeGroupOption => SearchFacetAttributeGroupOption::fromArray($o),
                self::normalizeListOfArrays($data['attributeGroup'] ?? []),
            ),
        );
    }

    public function isType(SearchFacetType $type): bool
    {
        return $this->type === $type || $this->rawType === $type->value;
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
