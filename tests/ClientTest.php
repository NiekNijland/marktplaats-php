<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use NiekNijland\Marktplaats\Client;
use NiekNijland\Marktplaats\Data\Category;
use NiekNijland\Marktplaats\Data\SearchQuery;
use NiekNijland\Marktplaats\Exception\ClientException;
use NiekNijland\Marktplaats\Exception\GoneException;
use NiekNijland\Marktplaats\Exception\NotFoundException;
use NiekNijland\Marktplaats\Support\HttpTransport;
use NiekNijland\Marktplaats\Testing\FakeClock;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use ReflectionClass;

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

    public function test_search_excluded_categories_filters_top_block(): void
    {
        $payload = json_encode([
            'listings' => [
                ['itemId' => 'm-allowed', 'title' => 'Allowed', 'categoryId' => 710],
            ],
            'topBlock' => [
                ['itemId' => 'm-top-excluded', 'title' => 'Top Excluded', 'categoryId' => 723],
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
        $this->assertSame([], $result->topBlock);
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

    public function test_get_search_all_deduplicates_listings_across_pages(): void
    {
        $page1 = json_encode([
            'listings' => [
                ['itemId' => 'm-dup-1', 'title' => 'Listing One', 'categoryId' => 696],
                ['itemId' => 'm-dup-2', 'title' => 'Listing Two', 'categoryId' => 696],
            ],
            'totalResultCount' => 3,
            'maxAllowedPageNumber' => 2,
        ], JSON_THROW_ON_ERROR);

        $page2 = json_encode([
            'listings' => [
                ['itemId' => 'm-dup-2', 'title' => 'Listing Two duplicate', 'categoryId' => 696],
                ['itemId' => 'm-dup-3', 'title' => 'Listing Three', 'categoryId' => 696],
            ],
            'totalResultCount' => 3,
            'maxAllowedPageNumber' => 2,
        ], JSON_THROW_ON_ERROR);

        $mock = new MockHandler([
            new Response(200, [], $page1),
            new Response(200, [], $page2),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
        );

        $listings = array_values(iterator_to_array($client->getSearchAll(new SearchQuery(categoryId: 678, limit: 2))));

        $this->assertCount(3, $listings);
        $this->assertSame('m-dup-1', $listings[0]->itemId);
        $this->assertSame('m-dup-2', $listings[1]->itemId);
        $this->assertSame('m-dup-3', $listings[2]->itemId);
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

    public function test_get_listing_follows_redirect_response(): void
    {
        $history = [];
        $html = $this->loadFixture('listing-detail.html');
        $mock = new MockHandler([
            new Response(301, ['Location' => '/v/motoren/yamaha/m2355451324-test-listing']),
            new Response(200, [], $html),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => $stack]),
        );

        $detail = $client->getListing('https://www.marktplaats.nl/v/motoren/honda/m2355451324-test');

        $this->assertSame('m2355451324', $detail->itemId);
        $this->assertCount(2, $history);
        $this->assertSame(
            'https://www.marktplaats.nl/v/motoren/honda/m2355451324-test',
            (string) $history[0]['request']->getUri(),
        );
        $this->assertSame(
            'https://www.marktplaats.nl/v/motoren/yamaha/m2355451324-test-listing',
            (string) $history[1]['request']->getUri(),
        );
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

    public function test_get_listing_404_throws_not_found_exception(): void
    {
        $mock = new MockHandler([
            new Response(404),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
        );

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('not found');
        $this->expectExceptionCode(404);

        $client->getListing('https://www.marktplaats.nl/v/motoren/nonexistent');
    }

    public function test_get_listing_410_throws_gone_exception(): void
    {
        $mock = new MockHandler([
            new Response(410),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
        );

        $this->expectException(GoneException::class);
        $this->expectExceptionCode(410);

        $client->getListing('https://www.marktplaats.nl/v/motoren/removed');
    }

    public function test_get_listing_200_expired_page_throws_gone_exception(): void
    {
        $expiredHtml = '<!doctype html><html lang="nl"><body><h1>Deze advertentie is helaas verlopen</h1><script>window.__CONFIG__ = '.json_encode([
            'itemId' => 'm2373093381',
            'eVipSimilarItems' => [
                [
                    'title' => 'Alternative listing',
                ],
            ],
        ], JSON_THROW_ON_ERROR).';</script></body></html>';

        $mock = new MockHandler([
            new Response(200, [], $expiredHtml),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
        );

        $this->expectException(GoneException::class);
        $this->expectExceptionCode(410);

        $client->getListing('https://www.marktplaats.nl/v/motoren/removed-but-200');
    }

    public function test_gone_exception_is_instance_of_not_found_exception(): void
    {
        $exception = new GoneException('Gone', 410);

        $this->assertInstanceOf(NotFoundException::class, $exception);
        $this->assertInstanceOf(ClientException::class, $exception);
    }

    public function test_search_requests_include_api_browser_headers(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');
        $history = [];

        $mock = new MockHandler([
            new Response(200, [], $fixture),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => $stack]),
        );

        $client->getSearch(new SearchQuery(categoryId: 678));

        $this->assertCount(1, $history);
        $request = $history[0]['request'];

        $this->assertSame('application/json, text/plain, */*', $request->getHeaderLine('Accept'));
        $this->assertSame('nl-NL,nl;q=0.9,en-US;q=0.8,en;q=0.7', $request->getHeaderLine('Accept-Language'));
        $this->assertSame('gzip, deflate', $request->getHeaderLine('Accept-Encoding'));
        $this->assertStringContainsString('Chrome/131.0.0.0', $request->getHeaderLine('User-Agent'));
        $this->assertFalse($request->hasHeader('Upgrade-Insecure-Requests'));
    }

    public function test_listing_requests_include_document_browser_headers(): void
    {
        $history = [];
        $mock = new MockHandler([
            new Response(200, [], $this->loadFixture('listing-detail.html')),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => $stack]),
        );

        $client->getListing('https://www.marktplaats.nl/v/motoren/honda/m2355451324-test');

        $this->assertCount(1, $history);
        $request = $history[0]['request'];

        $this->assertSame(
            'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            $request->getHeaderLine('Accept'),
        );
        $this->assertSame('1', $request->getHeaderLine('Upgrade-Insecure-Requests'));
        $this->assertSame('nl-NL,nl;q=0.9,en-US;q=0.8,en;q=0.7', $request->getHeaderLine('Accept-Language'));
    }

    public function test_custom_headers_override_built_in_profiles(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');
        $history = [];
        $mock = new MockHandler([
            new Response(200, [], $fixture),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => $stack]),
            defaultHeaders: [
                'User-Agent' => 'Custom Agent',
                'Accept' => 'application/custom',
                'X-Test' => 'anti-blocking',
            ],
        );

        $client->getSearch(new SearchQuery(categoryId: 678));

        $request = $history[0]['request'];

        $this->assertSame('Custom Agent', $request->getHeaderLine('User-Agent'));
        $this->assertSame('application/custom', $request->getHeaderLine('Accept'));
        $this->assertSame('anti-blocking', $request->getHeaderLine('X-Test'));
        $this->assertFalse($request->hasHeader('Accept-Language'));
        $this->assertFalse($request->hasHeader('Upgrade-Insecure-Requests'));
    }

    public function test_cookie_header_coexists_with_default_headers(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');
        $history = [];

        $mock = new MockHandler([
            new Response(200, ['Set-Cookie' => 'session=abc123; Path=/'], $fixture),
            new Response(200, [], $fixture),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => $stack]),
            defaultHeaders: [
                'User-Agent' => 'Custom Agent',
                'Accept' => 'application/custom',
                'Cookie' => 'custom=ignored',
            ],
        );

        $client->getSearch(new SearchQuery(categoryId: 678, offset: 0));
        $client->getSearch(new SearchQuery(categoryId: 678, offset: 1));

        $this->assertCount(2, $history);
        $this->assertSame('Custom Agent', $history[1]['request']->getHeaderLine('User-Agent'));
        $this->assertSame('application/custom', $history[1]['request']->getHeaderLine('Accept'));
        $this->assertSame('session=abc123', $history[1]['request']->getHeaderLine('Cookie'));
    }

    public function test_reset_session_clears_cookie_header_for_follow_up_requests(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');
        $history = [];

        $mock = new MockHandler([
            new Response(200, ['Set-Cookie' => 'session=abc123; Path=/'], $fixture),
            new Response(200, [], $fixture),
            new Response(200, [], $fixture),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => $stack]),
        );

        $client->getSearch(new SearchQuery(categoryId: 678, offset: 0));
        $client->getSearch(new SearchQuery(categoryId: 678, offset: 1));
        $client->resetSession();
        $client->getSearch(new SearchQuery(categoryId: 678, offset: 2));

        $this->assertCount(3, $history);
        $this->assertFalse($history[0]['request']->hasHeader('Cookie'));
        $this->assertSame('session=abc123', $history[1]['request']->getHeaderLine('Cookie'));
        $this->assertFalse($history[2]['request']->hasHeader('Cookie'));
    }

    public function test_fake_clock_records_sleep_calls(): void
    {
        $clock = new FakeClock;

        $clock->sleepMilliseconds(150);
        $clock->sleepMilliseconds(75);

        $this->assertSame([150, 75], $clock->getSleepCalls());
    }

    public function test_negative_request_delay_throws(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('requestDelayMilliseconds must be 0 or greater');

        new Client(requestDelayMilliseconds: -1);
    }

    public function test_negative_request_delay_jitter_throws(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('requestDelayJitterMilliseconds must be 0 or greater');

        new Client(requestDelayJitterMilliseconds: -1);
    }

    public function test_request_delay_zero_does_not_sleep(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');
        $clock = new FakeClock;
        $mock = new MockHandler([
            new Response(200, [], $fixture),
            new Response(200, [], $fixture),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
            clock: $clock,
            requestDelayMilliseconds: 0,
            requestDelayJitterMilliseconds: 100,
        );

        $client->getSearch(new SearchQuery(categoryId: 678, offset: 0));
        $client->getSearch(new SearchQuery(categoryId: 678, offset: 1));

        $this->assertSame([], $clock->getSleepCalls());
    }

    public function test_request_delay_applied_between_requests(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');
        $clock = new FakeClock;
        $mock = new MockHandler([
            new Response(200, [], $fixture),
            new Response(200, [], $fixture),
            new Response(200, [], $fixture),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
            clock: $clock,
            requestDelayMilliseconds: 150,
            requestDelayJitterMilliseconds: 0,
        );

        $client->getSearch(new SearchQuery(categoryId: 678, offset: 0));
        $client->getSearch(new SearchQuery(categoryId: 678, offset: 1));
        $client->getSearch(new SearchQuery(categoryId: 678, offset: 2));

        $this->assertSame([150, 150], $clock->getSleepCalls());
    }

    public function test_no_delay_on_first_request(): void
    {
        $clock = new FakeClock;
        $mock = new MockHandler([
            new Response(200, [], $this->loadFixture('search-motorcycles.json')),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
            clock: $clock,
            requestDelayMilliseconds: 150,
        );

        $client->getSearch(new SearchQuery(categoryId: 678));

        $this->assertSame([], $clock->getSleepCalls());
    }

    public function test_retries_on_403_when_configured(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');

        $mock = new MockHandler([
            new Response(403),
            new Response(200, [], $fixture),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
            maxRetries: 1,
            retryDelayMilliseconds: 0,
        );

        $result = $client->getSearch(new SearchQuery(categoryId: 678));

        $this->assertSame(3, $result->totalResultCount);
    }

    public function test_403_resets_session_before_retry(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');
        $history = [];

        $mock = new MockHandler([
            new Response(200, ['Set-Cookie' => 'session=abc123; Path=/'], $fixture),
            new Response(403),
            new Response(200, [], $fixture),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => $stack]),
            maxRetries: 1,
            retryDelayMilliseconds: 0,
        );

        $client->getSearch(new SearchQuery(categoryId: 678, offset: 0));
        $client->getSearch(new SearchQuery(categoryId: 678, offset: 1));

        $this->assertCount(3, $history);
        $this->assertFalse($history[0]['request']->hasHeader('Cookie'));
        $this->assertSame('session=abc123', $history[1]['request']->getHeaderLine('Cookie'));
        $this->assertFalse($history[2]['request']->hasHeader('Cookie'));
    }

    public function test_403_throws_when_retries_exhausted(): void
    {
        $mock = new MockHandler([
            new Response(403),
            new Response(403),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
            maxRetries: 1,
            retryDelayMilliseconds: 0,
        );

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('authorization error');

        $client->getSearch(new SearchQuery(categoryId: 678));
    }

    public function test_403_throws_when_no_retries_configured(): void
    {
        $mock = new MockHandler([
            new Response(403),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
            maxRetries: 0,
        );

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('authorization error');

        $client->getSearch(new SearchQuery(categoryId: 678));
    }

    public function test_retries_on_429_when_configured(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');

        $mock = new MockHandler([
            new Response(429),
            new Response(200, [], $fixture),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
            maxRetries: 1,
            retryDelayMilliseconds: 0,
        );

        $result = $client->getSearch(new SearchQuery(categoryId: 678));

        $this->assertSame(3, $result->totalResultCount);
    }

    public function test_retry_delay_has_jitter(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');
        $clock = new FakeClock;
        $mock = new MockHandler([
            new Response(429),
            new Response(200, [], $fixture),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
            maxRetries: 1,
            retryDelayMilliseconds: 100,
            clock: $clock,
        );

        $client->getSearch(new SearchQuery(categoryId: 678));

        $this->assertCount(1, $clock->getSleepCalls());
        $this->assertGreaterThanOrEqual(75, $clock->getSleepCalls()[0]);
        $this->assertLessThanOrEqual(125, $clock->getSleepCalls()[0]);
    }

    public function test_retry_with_zero_delay_does_not_sleep(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');
        $clock = new FakeClock;
        $mock = new MockHandler([
            new Response(429),
            new Response(200, [], $fixture),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
            maxRetries: 1,
            retryDelayMilliseconds: 0,
            clock: $clock,
        );

        $client->getSearch(new SearchQuery(categoryId: 678));

        $this->assertSame([], $clock->getSleepCalls());
    }

    public function test_negative_max_requests_per_window_throws(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('maxRequestsPerWindow must be 0 or greater');

        new Client(maxRequestsPerWindow: -1);
    }

    public function test_request_window_limit_throws_when_limit_reached(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');
        $mock = new MockHandler([
            new Response(200, [], $fixture),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
            maxRequestsPerWindow: 1,
        );

        $client->getSearch(new SearchQuery(categoryId: 678, offset: 0));

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('resetStats()');

        $client->getSearch(new SearchQuery(categoryId: 678, offset: 1));
    }

    public function test_request_window_limit_resets_after_reset_stats(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');
        $mock = new MockHandler([
            new Response(200, [], $fixture),
            new Response(200, [], $fixture),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
            maxRequestsPerWindow: 1,
        );

        $client->getSearch(new SearchQuery(categoryId: 678, offset: 0));
        $client->resetStats();
        $result = $client->getSearch(new SearchQuery(categoryId: 678, offset: 1));

        $this->assertSame(3, $result->totalResultCount);
        $this->assertSame(1, $client->getStats()['requests']);
    }

    public function test_request_window_zero_means_unlimited(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');
        $mock = new MockHandler([
            new Response(200, [], $fixture),
            new Response(200, [], $fixture),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
            maxRequestsPerWindow: 0,
        );

        $client->getSearch(new SearchQuery(categoryId: 678, offset: 0));
        $client->getSearch(new SearchQuery(categoryId: 678, offset: 1));

        $this->assertSame(2, $client->getStats()['requests']);
    }

    public function test_stats_count_successful_requests(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');
        $mock = new MockHandler([
            new Response(200, [], $fixture),
            new Response(200, [], $fixture),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
        );

        $client->getSearch(new SearchQuery(categoryId: 678, offset: 0));
        $client->getSearch(new SearchQuery(categoryId: 678, offset: 1));

        $this->assertSame([
            'requests' => 2,
            'successes' => 2,
            'failures' => 0,
            'retries' => 0,
            'session_resets' => 0,
            'total_sleep_ms' => 0.0,
        ], array_diff_key($client->getStats(), ['last_request_at' => true]));
        $this->assertIsFloat($client->getStats()['last_request_at']);
    }

    public function test_stats_count_retries(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');
        $mock = new MockHandler([
            new Response(429),
            new Response(200, [], $fixture),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
            maxRetries: 1,
            retryDelayMilliseconds: 0,
        );

        $client->getSearch(new SearchQuery(categoryId: 678));

        $this->assertSame(2, $client->getStats()['requests']);
        $this->assertSame(1, $client->getStats()['retries']);
    }

    public function test_stats_count_failures(): void
    {
        $mock = new MockHandler([
            new Response(500),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
        );

        try {
            $client->getSearch(new SearchQuery(categoryId: 678));
            $this->fail('Expected exception was not thrown.');
        } catch (ClientException) {
            $this->assertSame(1, $client->getStats()['failures']);
        }
    }

    public function test_stats_count_session_resets_on_403(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');
        $mock = new MockHandler([
            new Response(403),
            new Response(200, [], $fixture),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
            maxRetries: 1,
            retryDelayMilliseconds: 0,
        );

        $client->getSearch(new SearchQuery(categoryId: 678));

        $this->assertSame(1, $client->getStats()['session_resets']);
    }

    public function test_stats_reset_on_reset_stats(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');
        $mock = new MockHandler([
            new Response(200, [], $fixture),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
        );

        $client->getSearch(new SearchQuery(categoryId: 678));
        $client->resetStats();

        $this->assertSame([
            'requests' => 0,
            'successes' => 0,
            'failures' => 0,
            'retries' => 0,
            'session_resets' => 0,
            'last_request_at' => null,
            'total_sleep_ms' => 0.0,
        ], $client->getStats());
    }

    public function test_stats_track_sleep_time(): void
    {
        $fixture = $this->loadFixture('search-motorcycles.json');
        $clock = new FakeClock;
        $mock = new MockHandler([
            new Response(200, [], $fixture),
            new Response(200, [], $fixture),
        ]);

        $client = new Client(
            httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]),
            clock: $clock,
            requestDelayMilliseconds: 120,
            requestDelayJitterMilliseconds: 0,
        );

        $client->getSearch(new SearchQuery(categoryId: 678, offset: 0));
        $client->getSearch(new SearchQuery(categoryId: 678, offset: 1));

        $this->assertSame([120], $clock->getSleepCalls());
        $this->assertSame(120.0, $client->getStats()['total_sleep_ms']);
    }

    public function test_proxy_url_applied_to_default_guzzle_client(): void
    {
        $client = new Client(proxyUrl: 'http://proxy.example.test:8080');

        $this->assertInstanceOf(GuzzleClient::class, $this->getUnderlyingHttpClient($client));
        $this->assertSame(
            'http://proxy.example.test:8080',
            $this->getUnderlyingHttpClient($client)->getConfig('proxy'),
        );
    }

    public function test_proxy_url_null_means_no_proxy(): void
    {
        $client = new Client(proxyUrl: null);

        $this->assertNull($this->getUnderlyingHttpClient($client)->getConfig('proxy'));
    }

    public function test_invalid_proxy_url_throws(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('proxyUrl must use http://, https://, or socks5://');

        new Client(proxyUrl: 'ftp://proxy.example.test');
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

    private function getUnderlyingHttpClient(Client $client): GuzzleClient
    {
        $clientReflection = new ReflectionClass($client);
        $transportProperty = $clientReflection->getProperty('transport');
        $transportProperty->setAccessible(true);

        /** @var HttpTransport $transport */
        $transport = $transportProperty->getValue($client);

        $transportReflection = new ReflectionClass($transport);
        $httpClientProperty = $transportReflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);

        /** @var HttpClientInterface $httpClient */
        $httpClient = $httpClientProperty->getValue($transport);

        $this->assertInstanceOf(GuzzleClient::class, $httpClient);

        return $httpClient;
    }

    private function loadFixture(string $filename): string
    {
        $path = __DIR__.'/Fixtures/'.$filename;
        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }
}
