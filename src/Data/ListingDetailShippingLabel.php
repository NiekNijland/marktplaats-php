<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class ListingDetailShippingLabel
{
    public function __construct(
        public string $label,
        public ?string $price = null,
        public ?string $carrierName = null,
        public ?string $deliveryMethod = null,
    ) {}

    /**
     * @return array{label: string, price: ?string, carrierName: ?string, deliveryMethod: ?string}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'price' => $this->price,
            'carrierName' => $this->carrierName,
            'deliveryMethod' => $this->deliveryMethod,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            label: $data['label'] ?? '',
            price: $data['price'] ?? null,
            carrierName: $data['carrierName'] ?? null,
            deliveryMethod: $data['deliveryMethod'] ?? null,
        );
    }
}
