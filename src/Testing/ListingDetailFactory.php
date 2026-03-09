<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Testing;

use NiekNijland\Marktplaats\Data\ListingDetail;

class ListingDetailFactory
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function make(array $overrides = []): ListingDetail
    {
        $defaults = [
            'itemId' => 'm'.random_int(1000000000, 9999999999),
            'title' => 'Test Listing Detail',
            'description' => 'A detailed description of the listing.',
            'adType' => 'OFFERED',
            'priceInfo' => [
                'priceCents' => 450000,
                'priceType' => 'FIXED',
            ],
            'seller' => [
                'id' => 12345,
                'name' => 'Test Seller',
                'pageUrl' => '/u/test-seller/12345/',
                'sellerType' => 'PRIVATE',
                'activeYears' => 3,
                'isAsqEnabled' => true,
                'showSellerReviews' => false,
                'showVerifications' => false,
                'financeAvailable' => false,
                'location' => [
                    'cityName' => 'Amsterdam',
                    'countryName' => 'Nederland',
                    'countryAbbreviation' => 'NL',
                    'isAbroad' => false,
                    'isOnCountryLevel' => false,
                    'latitude' => 52.3676,
                    'longitude' => 4.9041,
                ],
                'contactOptions' => [],
            ],
            'category' => [
                'id' => 696,
                'name' => 'Honda',
                'fullName' => 'Motoren | Honda',
                'parentId' => 678,
                'parentName' => 'Motoren',
            ],
            'stats' => [
                'viewCount' => 150,
                'favoritedCount' => 12,
                'since' => '2025-01-15',
            ],
            'bidsInfo' => [
                'isBiddingEnabled' => false,
                'isRemovingBidEnabled' => false,
                'currentMinimumBidCents' => null,
                'bids' => [],
            ],
            'shipping' => [
                'carriers' => [],
                'deliveryType' => null,
            ],
            'images' => [],
            'imageUrls' => [],
            'imageSizes' => [
                'XL' => '84',
                'M' => '82',
            ],
            'galleryAlt' => 'Test gallery alt',
            'attributes' => [
                ['label' => 'Bouwjaar', 'value' => '2020'],
                ['label' => 'Kilometerstand', 'value' => '15.000 km'],
            ],
            'traits' => [],
            'buyItNowEnabled' => false,
            'buyersProtectionAllowed' => false,
            'thinContent' => false,
            'isAutomotiveAd' => false,
            'isFreeAd' => false,
            'shippable' => false,
            'fullUrl' => 'https://www.marktplaats.nl/v/motoren/honda/m1234567890-test-listing',
        ];

        return ListingDetail::fromArray(array_merge($defaults, $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return ListingDetail[]
     */
    public static function makeMany(int $count, array $overrides = []): array
    {
        return array_map(fn (): ListingDetail => self::make($overrides), range(1, $count));
    }
}
