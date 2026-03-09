<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Testing;

use NiekNijland\Marktplaats\Data\SellerInformation;

class SellerInformationFactory
{
    public static function make(
        ?int $sellerId = 12345,
        ?string $sellerName = 'Test Seller',
        bool $showSoiUrl = true,
        bool $showWebsiteUrl = false,
        ?string $sellerWebsiteUrl = null,
        ?string $sellerLogoUrl = null,
        bool $isVerified = false,
    ): SellerInformation {
        return new SellerInformation(
            sellerId: $sellerId,
            sellerName: $sellerName,
            showSoiUrl: $showSoiUrl,
            showWebsiteUrl: $showWebsiteUrl,
            sellerWebsiteUrl: $sellerWebsiteUrl,
            sellerLogoUrl: $sellerLogoUrl,
            isVerified: $isVerified,
        );
    }
}
