<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class ListingDetailShippingCarrier
{
    /**
     * @param  list<ListingDetailShippingLabel>  $labels
     */
    public function __construct(
        public string $carrierId,
        public array $labels = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'carrierId' => $this->carrierId,
            'labels' => array_map(fn (ListingDetailShippingLabel $l): array => $l->toArray(), $this->labels),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            carrierId: $data['carrierId'] ?? '',
            labels: array_values(array_map(
                fn (array $label): ListingDetailShippingLabel => ListingDetailShippingLabel::fromArray($label),
                $data['labels'] ?? [],
            )),
        );
    }
}
