<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Tests\Testing;

use NiekNijland\Marktplaats\Data\Enums\PriceType;
use NiekNijland\Marktplaats\Testing\ListingFactory;
use NiekNijland\Marktplaats\Testing\LocationFactory;
use NiekNijland\Marktplaats\Testing\MotorcycleBrandCatalogFactory;
use NiekNijland\Marktplaats\Testing\MotorcycleBrandFactory;
use NiekNijland\Marktplaats\Testing\PriceInfoFactory;
use NiekNijland\Marktplaats\Testing\SearchResultFactory;
use NiekNijland\Marktplaats\Testing\SellerInformationFactory;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    public function test_listing_factory_creates_valid_listing(): void
    {
        $listing = ListingFactory::make();

        $this->assertNotEmpty($listing->itemId);
        $this->assertSame('Test Motorcycle Listing', $listing->title);
        $this->assertSame(696, $listing->categoryId);
        $this->assertNotNull($listing->fullUrl);
    }

    public function test_listing_factory_accepts_overrides(): void
    {
        $listing = ListingFactory::make([
            'title' => 'Custom Title',
            'categoryId' => 710,
        ]);

        $this->assertSame('Custom Title', $listing->title);
        $this->assertSame(710, $listing->categoryId);
    }

    public function test_listing_factory_make_many(): void
    {
        $listings = ListingFactory::makeMany(5);

        $this->assertCount(5, $listings);
    }

    public function test_price_info_factory(): void
    {
        $price = PriceInfoFactory::make();

        $this->assertSame(450000, $price->priceCents);
        $this->assertSame(PriceType::FIXED, $price->priceType);
    }

    public function test_location_factory(): void
    {
        $location = LocationFactory::make();

        $this->assertSame('Amsterdam', $location->cityName);
        $this->assertSame('NL', $location->countryAbbreviation);
        $this->assertFalse($location->abroad);
    }

    public function test_seller_information_factory(): void
    {
        $seller = SellerInformationFactory::make();

        $this->assertSame(12345, $seller->sellerId);
        $this->assertSame('Test Seller', $seller->sellerName);
    }

    public function test_motorcycle_brand_factory(): void
    {
        $brand = MotorcycleBrandFactory::make();

        $this->assertSame(696, $brand->categoryId);
        $this->assertSame('Honda', $brand->name);
        $this->assertSame(678, $brand->parentCategoryId);
    }

    public function test_motorcycle_brand_catalog_factory(): void
    {
        $catalog = MotorcycleBrandCatalogFactory::make();

        $this->assertSame(678, $catalog->sourceCategoryId);
        $this->assertCount(3, $catalog->brands);
    }

    public function test_search_result_factory(): void
    {
        $result = SearchResultFactory::make(totalResultCount: 42);

        $this->assertSame(42, $result->totalResultCount);
        $this->assertNotEmpty($result->listings);
    }

    public function test_search_result_factory_with_custom_listings(): void
    {
        $listings = ListingFactory::makeMany(3);
        $result = SearchResultFactory::make(listings: $listings, totalResultCount: 3);

        $this->assertCount(3, $result->listings);
    }
}
