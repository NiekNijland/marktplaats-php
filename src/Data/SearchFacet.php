<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class SearchFacet
{
    /**
     * @param  SearchFacetCategory[]  $categories
     * @param  SearchFacetAttributeGroupOption[]  $attributeGroup
     */
    public function __construct(
        public ?int $id,
        public ?string $key,
        public ?string $type,
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
            'type' => $this->type,
            'label' => $this->label,
            'singleSelect' => $this->singleSelect,
            'categoryId' => $this->categoryId,
            'categories' => array_map(fn (SearchFacetCategory $c) => $c->toArray(), $this->categories),
            'attributeGroup' => array_map(fn (SearchFacetAttributeGroupOption $o) => $o->toArray(), $this->attributeGroup),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            key: $data['key'] ?? null,
            type: $data['type'] ?? null,
            label: $data['label'] ?? null,
            singleSelect: $data['singleSelect'] ?? null,
            categoryId: $data['categoryId'] ?? null,
            categories: array_map(
                fn (array $c) => SearchFacetCategory::fromArray($c),
                $data['categories'] ?? [],
            ),
            attributeGroup: array_map(
                fn (array $o) => SearchFacetAttributeGroupOption::fromArray($o),
                $data['attributeGroup'] ?? [],
            ),
        );
    }
}
