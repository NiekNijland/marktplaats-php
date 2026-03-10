<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use NiekNijland\Marktplaats\Client;
use NiekNijland\Marktplaats\Data\Category;
use NiekNijland\Marktplaats\Data\SearchQuery;
use NiekNijland\Marktplaats\Exception\ClientException;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function test_search_returns_search_result(): void
    {
        $client = $this->createClientWithFixture('search-motorcycles.json');
        $result = $client->getSearch(new SearchQuery(categoryId: 678));

        $this->assertSame(3, $result->totalResultCount);
        $this->assertCount(3, $result->listings);
    }

    public function test_search_204_returns_empty_result(): void
    {
        $mock = new MockHandler([
            new Response(204, [], ''),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
        );

        $result = $client->getSearch(new SearchQuery);

        $this->assertSame(0, $result->totalResultCount);
        $this->assertSame([], $result->listings);
    }

    public function test_search_400_throws(): void
    {
        $mock = new MockHandler([
            new Response(400, [], '{"error":"Bad Request"}'),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
        );

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('400 Bad Request');

        $client->getSearch(new SearchQuery);
    }

    public function test_search_401_throws(): void
    {
        $mock = new MockHandler([
            new Response(401),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
        );

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('authorization error');

        $client->getSearch(new SearchQuery);
    }

    public function test_search_429_throws(): void
    {
        $mock = new MockHandler([
            new Response(429),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
        );

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('rate limit');

        $client->getSearch(new SearchQuery);
    }

    public function test_search_500_throws(): void
    {
        $mock = new MockHandler([
            new Response(500),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
        );

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('server error');

        $client->getSearch(new SearchQuery);
    }

    public function test_cache_stores_and_retrieves_result(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');
        $mock = new MockHandler([
            new Response(200, [], $fixture),
        ]);

        $cache = new ArrayCache;

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
            cache: $cache,
        );

        $query = new SearchQuery(categoryId: 678);

        // First call - fetches from API
        $result1 = $client->getSearch($query);
        $this->assertSame(3, $result1->totalResultCount);

        // Second call - should come from cache (mock is exhausted)
        $result2 = $client->getSearch($query);
        $this->assertSame(3, $result2->totalResultCount);
    }

    public function test_no_cache_always_hits_api(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');
        $mock = new MockHandler([
            new Response(200, [], $fixture),
            new Response(200, [], $fixture),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
        );

        $query = new SearchQuery(categoryId: 678);
        $client->getSearch($query);
        $client->getSearch($query);

        // Both requests were served (mock handler would throw if extra calls were made)
        $this->assertSame(0, $mock->count());
    }

    public function test_search_excluded_categories_filters_listings(): void
    {
        $client = $this->createClientWithFixture('search-motorcycles.json');
        $query = new SearchQuery(categoryId: 678);
        $result = $client->getSearch($query, [723, 724]);

        // Original fixture has 3 listings: categoryId 696, 710, 723
        // Category 723 is excluded by query
        $this->assertCount(2, $result->listings);

        foreach ($result->listings as $listing) {
            $this->assertNotContains($listing->categoryId, [723, 724]);
        }
    }

    public function test_search_without_exclusions_returns_all(): void
    {
        $client = $this->createClientWithFixture('search-motorcycles.json');
        $query = new SearchQuery(categoryId: 678);
        $result = $client->getSearch($query);

        $this->assertCount(3, $result->listings);
    }

    public function test_search_excluded_categories_preserves_original_total_count(): void
    {
        $client = $this->createClientWithFixture('search-motorcycles.json');
        $query = new SearchQuery(categoryId: 678);
        $result = $client->getSearch($query, [723, 724]);

        // totalResultCount is the original API count, not the filtered count
        $this->assertSame(3, $result->totalResultCount);
    }

    public function test_get_search_all_yields_listings(): void
    {
        $client = $this->createClientWithFixture('search-motorcycles.json');
        $query = new SearchQuery(categoryId: 678);

        $listings = [];

        foreach ($client->getSearchAll($query) as $listing) {
            $listings[] = $listing;
        }

        // topBlock should NOT be yielded
        $this->assertCount(3, $listings);
        $this->assertSame('m2100000001', $listings[0]->itemId);
    }

    public function test_get_search_all_applies_excluded_categories(): void
    {
        $client = $this->createClientWithFixture('search-motorcycles.json');
        $query = new SearchQuery(categoryId: 678);

        $listings = iterator_to_array($client->getSearchAll($query, [723]));

        $this->assertCount(2, $listings);
    }

    public function test_get_search_all_continues_after_fully_excluded_page(): void
    {
        $page1 = json_encode([
            'listings' => [
                ['itemId' => 'm-ex-1', 'title' => 'Excluded 1', 'categoryId' => 723],
                ['itemId' => 'm-ex-2', 'title' => 'Excluded 2', 'categoryId' => 723],
            ],
            'totalResultCount' => 2,
            'maxAllowedPageNumber' => 2,
        ], JSON_THROW_ON_ERROR);

        $page2 = json_encode([
            'listings' => [
                ['itemId' => 'm-ok-1', 'title' => 'Allowed', 'categoryId' => 710],
            ],
            'totalResultCount' => 2,
            'maxAllowedPageNumber' => 2,
        ], JSON_THROW_ON_ERROR);

        $mock = new MockHandler([
            new Response(200, [], $page1),
            new Response(200, [], $page2),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
        );

        $query = new SearchQuery(categoryId: 678);

        $listings = iterator_to_array($client->getSearchAll($query, [723]));

        $this->assertCount(1, $listings);
        $this->assertSame('m-ok-1', $listings[0]->itemId);
    }

    public function test_search_excluded_categories_keeps_null_category_listing(): void
    {
        $payload = json_encode([
            'listings' => [
                ['itemId' => 'm-null-cat', 'title' => 'Unknown Category', 'categoryId' => null],
                ['itemId' => 'm-excluded', 'title' => 'Excluded', 'categoryId' => 723],
            ],
            'totalResultCount' => 2,
            'maxAllowedPageNumber' => 1,
        ], JSON_THROW_ON_ERROR);

        $mock = new MockHandler([
            new Response(200, [], $payload),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
        );

        $result = $client->getSearch(new SearchQuery(categoryId: 678), [723]);

        $this->assertCount(1, $result->listings);
        $this->assertSame('m-null-cat', $result->listings[0]->itemId);
    }

    public function test_get_search_all_stops_on_empty_listings(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'listings' => [],
                'totalResultCount' => 0,
                'maxAllowedPageNumber' => 0,
            ])),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
        );

        $listings = iterator_to_array($client->getSearchAll(new SearchQuery));

        $this->assertSame([], $listings);
    }

    public function test_category_catalog_discovery(): void
    {
        $client = $this->createClientWithFixture('search-motorcycle-brand-catalog.json');
        $catalog = $client->getCategoryCatalog(678);

        $this->assertSame(678, $catalog->parentCategoryId);
        $this->assertNotEmpty($catalog->categories);

        $categoryNames = array_map(fn (Category $c): ?string => $c->name, $catalog->categories);
        $this->assertContains('Honda', $categoryNames);
        $this->assertContains('Oldtimers', $categoryNames);
    }

    public function test_category_catalog_cached(): void
    {
        $fixture = $this->loadFixture('search-motorcycle-brand-catalog.json');
        $mock = new MockHandler([
            new Response(200, [], $fixture),
        ]);

        $cache = new ArrayCache;
        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
            cache: $cache,
        );

        $catalog1 = $client->getCategoryCatalog(678);
        $catalog2 = $client->getCategoryCatalog(678);

        $this->assertSame(count($catalog1->categories), count($catalog2->categories));
    }

    public function test_filter_catalog_discovery(): void
    {
        $client = $this->createClientWithFixture('search-motorcycles.json');
        $catalog = $client->getFilterCatalog(678);

        $this->assertSame(678, $catalog->categoryId);
        $this->assertNull($catalog->subCategoryId);
        $this->assertNotEmpty($catalog->facets);
        $this->assertNotEmpty($catalog->getRangeFacets());
        $this->assertNotEmpty($catalog->getGroupFacets());
        $this->assertNotNull($catalog->findByKey('brand'));
    }

    public function test_filter_catalog_cached(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');
        $mock = new MockHandler([
            new Response(200, [], $fixture),
        ]);

        $cache = new ArrayCache;
        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
            cache: $cache,
        );

        $catalog1 = $client->getFilterCatalog(678);
        $catalog2 = $client->getFilterCatalog(678);

        $this->assertSame(count($catalog1->facets), count($catalog2->facets));
    }

    public function test_filter_catalog_with_l2_category(): void
    {
        $client = $this->createClientWithFixture('search-motorcycles.json');
        $catalog = $client->getFilterCatalog(678, 696);

        $this->assertSame(678, $catalog->categoryId);
        $this->assertSame(696, $catalog->subCategoryId);
    }

    public function test_search_query_with_l1_and_l2_category(): void
    {
        $client = $this->createClientWithFixture('search-motorcycles-honda.json');

        $query = new SearchQuery(
            categoryId: 678,
            subCategoryId: 696,
        );

        $result = $client->getSearch($query);

        $this->assertNotEmpty($result->listings);
    }

    public function test_get_listing_returns_listing_detail(): void
    {
        $html = $this->loadFixture('listing-detail.html');
        $mock = new MockHandler([
            new Response(200, [], $html),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
        );

        $detail = $client->getListing('https://www.marktplaats.nl/v/motoren/honda/m2355451324-test');

        $this->assertSame('Yamaha MT-07 ABS + handvatverwarming', $detail->title);
        $this->assertSame('m2355451324', $detail->itemId);
    }

    public function test_get_listing_resolves_relative_url(): void
    {
        $html = $this->loadFixture('listing-detail.html');
        $mock = new MockHandler([
            new Response(200, [], $html),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
        );

        $detail = $client->getListing('/v/motoren/honda/m2355451324-test');

        $this->assertNotEmpty($detail->itemId);
    }

    public function test_get_listing_caches_result(): void
    {
        $html = $this->loadFixture('listing-detail.html');
        $mock = new MockHandler([
            new Response(200, [], $html),
        ]);

        $cache = new ArrayCache;

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
            cache: $cache,
        );

        $url = 'https://www.marktplaats.nl/v/motoren/honda/m2355451324-test';

        // First call — fetches from API
        $detail1 = $client->getListing($url);

        // Second call — should come from cache (mock is exhausted)
        $detail2 = $client->getListing($url);

        $this->assertSame($detail1->itemId, $detail2->itemId);
        $this->assertSame($detail1->title, $detail2->title);
    }

    public function test_get_listing_404_throws(): void
    {
        $mock = new MockHandler([
            new Response(404),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
        );

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('not found');

        $client->getListing('https://www.marktplaats.nl/v/motoren/nonexistent');
    }

    private function createClientWithFixture(string $fixture): Client
    {
        $json = $this->loadFixture($fixture);
        $mock = new MockHandler([
            new Response(200, [], $json),
        ]);

        return new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
        );
    }

    private function loadFixture(string $filename): string
    {
        $path = __DIR__.'/Fixtures/'.$filename;
        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }
}
