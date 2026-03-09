<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Tests\Testing;

use NiekNijland\Marktplaats\Data\Enums\PriceType;
use NiekNijland\Marktplaats\Data\ListingDetail;
use NiekNijland\Marktplaats\Testing\CategoryCatalogFactory;
use NiekNijland\Marktplaats\Testing\CategoryFactory;
use NiekNijland\Marktplaats\Testing\ListingDetailFactory;
use NiekNijland\Marktplaats\Testing\ListingFactory;
use NiekNijland\Marktplaats\Testing\LocationFactory;
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

    public function test_category_factory(): void
    {
        $category = CategoryFactory::make();

        $this->assertSame(696, $category->id);
        $this->assertSame('Honda', $category->name);
        $this->assertSame(678, $category->parentId);
    }

    public function test_category_catalog_factory(): void
    {
        $catalog = CategoryCatalogFactory::make();

        $this->assertSame(678, $catalog->parentCategoryId);
        $this->assertCount(3, $catalog->categories);
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

    public function test_listing_detail_factory_creates_valid_detail(): void
    {
        $detail = ListingDetailFactory::make();

        $this->assertNotEmpty($detail->itemId);
        $this->assertSame('Test Listing Detail', $detail->title);
        $this->assertSame('A detailed description of the listing.', $detail->description);
        $this->assertSame('OFFERED', $detail->adType);
        $this->assertNotNull($detail->priceInfo);
        $this->assertSame(450000, $detail->priceInfo->priceCents);
        $this->assertNotNull($detail->seller);
        $this->assertSame('Test Seller', $detail->seller->name);
        $this->assertNotNull($detail->category);
        $this->assertSame(696, $detail->category->id);
        $this->assertNotNull($detail->stats);
        $this->assertSame(150, $detail->stats->viewCount);
    }

    public function test_listing_detail_factory_accepts_overrides(): void
    {
        $detail = ListingDetailFactory::make([
            'title' => 'Custom Detail Title',
            'description' => 'Custom description',
        ]);

        $this->assertSame('Custom Detail Title', $detail->title);
        $this->assertSame('Custom description', $detail->description);
    }

    public function test_listing_detail_factory_make_many(): void
    {
        $details = ListingDetailFactory::makeMany(4);

        $this->assertCount(4, $details);
    }

    public function test_listing_detail_factory_roundtrip(): void
    {
        $original = ListingDetailFactory::make();
        $array = $original->toArray();
        $restored = ListingDetail::fromArray($array);

        $this->assertSame($original->itemId, $restored->itemId);
        $this->assertSame($original->title, $restored->title);
        $this->assertSame($original->description, $restored->description);
        $this->assertSame($original->fullUrl, $restored->fullUrl);
    }
}
