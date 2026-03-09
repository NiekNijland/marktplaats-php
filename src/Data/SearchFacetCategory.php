<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class SearchFacetCategory
{
    public function __construct(
        public ?int $id,
        public ?string $label,
        public ?string $key,
        public ?int $parentId,
        public ?string $parentKey,
        public bool $selected,
        public ?bool $isValuableForSeo,
        public ?bool $dominant,
        public ?int $histogramCount,
    ) {}

    /**
     * @return array{id: ?int, label: ?string, key: ?string, parentId: ?int, parentKey: ?string, selected: bool, isValuableForSeo: ?bool, dominant: ?bool, histogramCount: ?int}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'key' => $this->key,
            'parentId' => $this->parentId,
            'parentKey' => $this->parentKey,
            'selected' => $this->selected,
            'isValuableForSeo' => $this->isValuableForSeo,
            'dominant' => $this->dominant,
            'histogramCount' => $this->histogramCount,
        ];
    }

    /**
     * @param  array{id?: ?int, label?: ?string, key?: ?string, parentId?: ?int, parentKey?: mixed, selected?: bool, isValuableForSeo?: ?bool, dominant?: ?bool, histogramCount?: ?int}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            label: $data['label'] ?? null,
            key: $data['key'] ?? null,
            parentId: $data['parentId'] ?? null,
            parentKey: is_string($data['parentKey'] ?? null) ? $data['parentKey'] : null,
            selected: $data['selected'] ?? false,
            isValuableForSeo: $data['isValuableForSeo'] ?? null,
            dominant: $data['dominant'] ?? null,
            histogramCount: $data['histogramCount'] ?? null,
        );
    }
}
