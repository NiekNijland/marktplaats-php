<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class ListingDetailShipping
{
    /**
     * @param  list<ListingDetailShippingCarrier>  $carriers
     */
    public function __construct(
        public array $carriers = [],
        public ?ListingDetailDeliveryType $deliveryType = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'carriers' => array_map(fn (ListingDetailShippingCarrier $c): array => $c->toArray(), $this->carriers),
            'deliveryType' => $this->deliveryType?->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            carriers: array_values(array_map(
                fn (array $carrier): ListingDetailShippingCarrier => ListingDetailShippingCarrier::fromArray($carrier),
                $data['augmentedLabels'] ?? $data['carriers'] ?? [],
            )),
            deliveryType: isset($data['deliveryType']) ? ListingDetailDeliveryType::fromArray($data['deliveryType']) : null,
        );
    }
}
