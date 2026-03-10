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
            id: isset($data['id']) ? $data['id'] : null,
            label: is_string($data['label'] ?? null) ? $data['label'] : null,
            key: is_string($data['key'] ?? null) ? $data['key'] : null,
            parentId: isset($data['parentId']) ? $data['parentId'] : null,
            parentKey: is_string($data['parentKey'] ?? null) ? $data['parentKey'] : null,
            selected: (bool) ($data['selected'] ?? false),
            isValuableForSeo: is_bool($data['isValuableForSeo'] ?? null) ? $data['isValuableForSeo'] : null,
            dominant: is_bool($data['dominant'] ?? null) ? $data['dominant'] : null,
            histogramCount: isset($data['histogramCount']) ? $data['histogramCount'] : null,
        );
    }
}
