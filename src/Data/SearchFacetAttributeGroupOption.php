<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class SearchFacetAttributeGroupOption
{
    public function __construct(
        public ?string $attributeValueKey,
        public ?int $attributeValueId,
        public ?string $attributeValueLabel,
        public ?int $histogramCount,
        public bool $selected,
        public ?bool $isValuableForSeo,
        public ?bool $default,
    ) {}

    /**
     * @return array{attributeValueKey: ?string, attributeValueId: ?int, attributeValueLabel: ?string, histogramCount: ?int, selected: bool, isValuableForSeo: ?bool, default: ?bool}
     */
    public function toArray(): array
    {
        return [
            'attributeValueKey' => $this->attributeValueKey,
            'attributeValueId' => $this->attributeValueId,
            'attributeValueLabel' => $this->attributeValueLabel,
            'histogramCount' => $this->histogramCount,
            'selected' => $this->selected,
            'isValuableForSeo' => $this->isValuableForSeo,
            'default' => $this->default,
        ];
    }

    /**
     * @param  array{attributeValueKey?: ?string, attributeValueId?: ?int, attributeValueLabel?: ?string, histogramCount?: ?int, selected?: bool, isValuableForSeo?: ?bool, default?: ?bool}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            attributeValueKey: $data['attributeValueKey'] ?? null,
            attributeValueId: $data['attributeValueId'] ?? null,
            attributeValueLabel: $data['attributeValueLabel'] ?? null,
            histogramCount: $data['histogramCount'] ?? null,
            selected: $data['selected'] ?? false,
            isValuableForSeo: $data['isValuableForSeo'] ?? null,
            default: $data['default'] ?? null,
        );
    }
}
