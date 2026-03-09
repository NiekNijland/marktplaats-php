<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Tests\Testing;

use NiekNijland\Marktplaats\Data\SearchQuery;
use NiekNijland\Marktplaats\Exception\ClientException;
use NiekNijland\Marktplaats\Testing\CategoryCatalogFactory;
use NiekNijland\Marktplaats\Testing\FakeClient;
use NiekNijland\Marktplaats\Testing\ListingDetailFactory;
use NiekNijland\Marktplaats\Testing\ListingFactory;
use NiekNijland\Marktplaats\Testing\SearchResultFactory;
use PHPUnit\Framework\TestCase;

class FakeClientTest extends TestCase
{
    public function test_fake_returns_seeded_result(): void
    {
        $seeded = SearchResultFactory::make(totalResultCount: 42);
        $fake = new FakeClient;
        $fake->seedSearchResult($seeded);

        $result = $fake->getSearch(new SearchQuery);

        $this->assertSame(42, $result->totalResultCount);
    }

    public function test_fake_returns_empty_when_not_seeded(): void
    {
        $fake = new FakeClient;
        $result = $fake->getSearch(new SearchQuery);

        $this->assertSame(0, $result->totalResultCount);
        $this->assertSame([], $result->listings);
    }

    public function test_fake_should_throw(): void
    {
        $fake = new FakeClient;
        $fake->shouldThrow(new ClientException('test error'));

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('test error');

        $fake->getSearch(new SearchQuery);
    }

    public function test_fake_records_calls(): void
    {
        $fake = new FakeClient;
        $fake->getSearch(new SearchQuery);

        $calls = $fake->getRecordedCalls();
        $this->assertCount(1, $calls);
        $this->assertSame('getSearch', $calls[0]->method);
    }

    public function test_assert_called(): void
    {
        $fake = new FakeClient;
        $fake->getSearch(new SearchQuery);

        $fake->assertCalled('getSearch');
    }

    public function test_assert_not_called(): void
    {
        $fake = new FakeClient;

        $fake->assertNotCalled('getSearch');
    }

    public function test_assert_called_times(): void
    {
        $fake = new FakeClient;
        $fake->getSearch(new SearchQuery);
        $fake->getSearch(new SearchQuery);

        $fake->assertCalledTimes('getSearch', 2);
    }

    public function test_fake_search_with_excluded_categories(): void
    {
        $seeded = SearchResultFactory::make(
            totalResultCount: 10,
            listings: ListingFactory::makeMany(3, ['categoryId' => 723]),
        );
        $fake = new FakeClient;
        $fake->seedSearchResult($seeded);

        $result = $fake->getSearch(new SearchQuery(excludedCategoryIds: [723]));

        $this->assertSame(10, $result->totalResultCount);
        $this->assertSame([], $result->listings);
        $fake->assertCalled('getSearch');
    }

    public function test_fake_search_with_excluded_categories_keeps_null_category(): void
    {
        $seeded = SearchResultFactory::make(
            listings: [
                ListingFactory::make(['itemId' => 'm-null', 'categoryId' => null]),
                ListingFactory::make(['itemId' => 'm-excluded', 'categoryId' => 723]),
            ],
        );
        $fake = new FakeClient;
        $fake->seedSearchResult($seeded);

        $result = $fake->getSearch(new SearchQuery(excludedCategoryIds: [723]));

        $this->assertCount(1, $result->listings);
        $this->assertSame('m-null', $result->listings[0]->itemId);
    }

    public function test_fake_category_catalog(): void
    {
        $catalog = CategoryCatalogFactory::make();
        $fake = new FakeClient;
        $fake->seedCategoryCatalog($catalog);

        $result = $fake->getCategoryCatalog(678);

        $this->assertSame(678, $result->parentCategoryId);
        $this->assertNotEmpty($result->categories);
    }

    public function test_fake_category_catalog_throws_when_not_seeded(): void
    {
        $fake = new FakeClient;

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('No category catalog seeded');

        $fake->getCategoryCatalog(678);
    }

    public function test_fake_get_search_all(): void
    {
        $seeded = SearchResultFactory::make(
            listings: ListingFactory::makeMany(3),
        );
        $fake = new FakeClient;
        $fake->seedSearchResult($seeded);

        $listings = iterator_to_array($fake->getSearchAll(new SearchQuery));

        $this->assertCount(3, $listings);
        $fake->assertCalled('getSearchAll');
    }

    public function test_fake_reset_session_records_call(): void
    {
        $fake = new FakeClient;
        $fake->resetSession();

        $fake->assertCalled('resetSession');
    }

    public function test_multiple_seeded_results_consumed_in_order(): void
    {
        $fake = new FakeClient;
        $fake->seedSearchResult(SearchResultFactory::make(totalResultCount: 1));
        $fake->seedSearchResult(SearchResultFactory::make(totalResultCount: 2));

        $this->assertSame(1, $fake->getSearch(new SearchQuery)->totalResultCount);
        $this->assertSame(2, $fake->getSearch(new SearchQuery)->totalResultCount);
        $this->assertSame(0, $fake->getSearch(new SearchQuery)->totalResultCount); // exhausted
    }

    public function test_fake_get_listing(): void
    {
        $detail = ListingDetailFactory::make(['title' => 'Seeded Detail']);
        $fake = new FakeClient;
        $fake->seedListingDetail($detail);

        $result = $fake->getListing('https://www.marktplaats.nl/v/motoren/honda/m123');

        $this->assertSame('Seeded Detail', $result->title);
        $fake->assertCalled('getListing');
    }

    public function test_fake_get_listing_throws_when_not_seeded(): void
    {
        $fake = new FakeClient;

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('No listing detail seeded');

        $fake->getListing('https://www.marktplaats.nl/v/motoren/honda/m123');
    }

    public function test_fake_multiple_listing_details_consumed_in_order(): void
    {
        $fake = new FakeClient;
        $fake->seedListingDetail(ListingDetailFactory::make(['title' => 'First']));
        $fake->seedListingDetail(ListingDetailFactory::make(['title' => 'Second']));

        $this->assertSame('First', $fake->getListing('/v/test/m1')->title);
        $this->assertSame('Second', $fake->getListing('/v/test/m2')->title);
    }
}
