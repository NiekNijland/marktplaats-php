<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use NiekNijland\Marktplaats\Client;
use NiekNijland\Marktplaats\Data\MotorcycleBrand;
use NiekNijland\Marktplaats\Data\MotorcycleSearchQuery;
use NiekNijland\Marktplaats\Data\SearchQuery;
use NiekNijland\Marktplaats\Exception\ClientException;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function test_search_returns_search_result(): void
    {
        $client = $this->createClientWithFixture('search-motorcycles.json');
        $result = $client->getSearch(new SearchQuery(l1CategoryId: 678));

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

        $query = new SearchQuery(l1CategoryId: 678);

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

        $query = new SearchQuery(l1CategoryId: 678);
        $client->getSearch($query);
        $client->getSearch($query);

        // Both requests were served (mock handler would throw if extra calls were made)
        $this->assertSame(0, $mock->count());
    }

    public function test_motorcycle_search_strict_mode_filters_non_bike_categories(): void
    {
        $client = $this->createClientWithFixture('search-motorcycles.json');
        $query = new MotorcycleSearchQuery(strictMode: true);
        $result = $client->getMotorcycleSearch($query);

        // Original fixture has 3 listings: categoryId 696, 710, 723
        // Category 723 is in STRICT_MODE_EXCLUDED_CATEGORIES
        $this->assertCount(2, $result->listings);

        foreach ($result->listings as $listing) {
            $this->assertNotContains(
                $listing->categoryId,
                MotorcycleSearchQuery::STRICT_MODE_EXCLUDED_CATEGORIES,
            );
        }
    }

    public function test_motorcycle_search_non_strict_returns_all(): void
    {
        $client = $this->createClientWithFixture('search-motorcycles.json');
        $query = new MotorcycleSearchQuery(strictMode: false);
        $result = $client->getMotorcycleSearch($query);

        $this->assertCount(3, $result->listings);
    }

    public function test_motorcycle_search_preserves_original_total_count(): void
    {
        $client = $this->createClientWithFixture('search-motorcycles.json');
        $query = new MotorcycleSearchQuery(strictMode: true);
        $result = $client->getMotorcycleSearch($query);

        // totalResultCount is the original API count, not the filtered count
        $this->assertSame(3, $result->totalResultCount);
    }

    public function test_get_search_all_yields_listings(): void
    {
        $client = $this->createClientWithFixture('search-motorcycles.json');
        $query = new SearchQuery(l1CategoryId: 678);

        $listings = [];

        foreach ($client->getSearchAll($query) as $listing) {
            $listings[] = $listing;
        }

        // topBlock should NOT be yielded
        $this->assertCount(3, $listings);
        $this->assertSame('m2100000001', $listings[0]->itemId);
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

    public function test_brand_catalog_discovery(): void
    {
        $client = $this->createClientWithFixture('search-motorcycle-brand-catalog.json');
        $catalog = $client->getMotorcycleBrandCatalog();

        $this->assertSame(678, $catalog->sourceCategoryId);
        $this->assertNotEmpty($catalog->brands);

        $brandNames = array_map(fn ($b) => $b->name, $catalog->brands);
        $this->assertContains('Honda', $brandNames);
        $this->assertNotContains('Oldtimers', $brandNames);
    }

    public function test_brand_catalog_cached(): void
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

        $catalog1 = $client->getMotorcycleBrandCatalog();
        $catalog2 = $client->getMotorcycleBrandCatalog();

        $this->assertSame(count($catalog1->brands), count($catalog2->brands));
    }

    public function test_motorcycle_query_with_brand(): void
    {
        $client = $this->createClientWithFixture('search-motorcycles-honda.json');

        $brand = new MotorcycleBrand(
            categoryId: 696,
            key: 'honda',
            name: 'Honda',
            fullName: 'Motoren | Honda',
            parentCategoryId: 678,
        );

        $query = new MotorcycleSearchQuery(brand: $brand);
        $result = $client->getSearch($query);

        $this->assertNotEmpty($result->listings);
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
