<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Testing;

use NiekNijland\Marktplaats\Data\Listing;

class ListingFactory
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function make(array $overrides = []): Listing
    {
        $defaults = [
            'itemId' => 'm'.random_int(1000000000, 9999999999),
            'title' => 'Test Motorcycle Listing',
            'description' => 'A great motorcycle for sale.',
            'categorySpecificDescription' => null,
            'categoryId' => 696,
            'vipUrl' => '/v/motoren/honda/m1234567890',
            'fullUrl' => 'https://www.marktplaats.nl/v/motoren/honda/m1234567890',
            'priceInfo' => null,
            'location' => null,
            'imageUrls' => [],
            'pictures' => [],
            'sellerInformation' => null,
            'attributes' => [],
            'extendedAttributes' => [],
            'traits' => [],
            'verticals' => [],
            'date' => '2025-01-15',
            'priorityProduct' => 'NONE',
            'reserved' => false,
            'searchType' => 'TokenMatch',
            'thinContent' => false,
            'videoOnVip' => false,
            'urgencyFeatureActive' => false,
            'napAvailable' => false,
            'trackingData' => null,
            'pageLocation' => null,
            'opvalStickerText' => null,
            'highlights' => [],
            'trustIndicators' => [],
        ];

        return Listing::fromArray(array_merge($defaults, $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return Listing[]
     */
    public static function makeMany(int $count, array $overrides = []): array
    {
        return array_map(fn () => self::make($overrides), range(1, $count));
    }
}
