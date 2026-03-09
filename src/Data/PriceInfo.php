<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

use NiekNijland\Marktplaats\Data\Enums\PriceType;

readonly class PriceInfo
{
    public function __construct(
        public ?int $priceCents,
        public ?PriceType $priceType,
    ) {}

    /**
     * @return array{priceCents: ?int, priceType: ?string}
     */
    public function toArray(): array
    {
        return [
            'priceCents' => $this->priceCents,
            'priceType' => $this->priceType?->value,
        ];
    }

    /**
     * @param  array{priceCents?: ?int, priceType?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            priceCents: $data['priceCents'] ?? null,
            priceType: isset($data['priceType']) ? PriceType::tryFrom($data['priceType']) : null,
        );
    }
}
