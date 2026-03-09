<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Testing;

use NiekNijland\Marktplaats\Data\Enums\PriceType;
use NiekNijland\Marktplaats\Data\PriceInfo;

class PriceInfoFactory
{
    public static function make(
        ?int $priceCents = 450000,
        ?PriceType $priceType = PriceType::FIXED,
    ): PriceInfo {
        return new PriceInfo(
            priceCents: $priceCents,
            priceType: $priceType,
        );
    }
}
