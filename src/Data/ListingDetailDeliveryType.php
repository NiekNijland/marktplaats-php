<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class ListingDetailDeliveryType
{
    public function __construct(
        public ?string $attributeLabel = null,
        public ?string $attributeValueLabel = null,
        public ?string $attributeValueKey = null,
    ) {}

    /**
     * @return array{attributeLabel: ?string, attributeValueLabel: ?string, attributeValueKey: ?string}
     */
    public function toArray(): array
    {
        return [
            'attributeLabel' => $this->attributeLabel,
            'attributeValueLabel' => $this->attributeValueLabel,
            'attributeValueKey' => $this->attributeValueKey,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            attributeLabel: $data['attributeLabel'] ?? null,
            attributeValueLabel: $data['attributeValueLabel'] ?? null,
            attributeValueKey: $data['attributeValueKey'] ?? null,
        );
    }
}
