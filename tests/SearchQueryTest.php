<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Tests;

use NiekNijland\Marktplaats\Data\Enums\SortBy;
use NiekNijland\Marktplaats\Data\Enums\SortOrder;
use NiekNijland\Marktplaats\Data\Enums\ViewOptionKind;
use NiekNijland\Marktplaats\Data\MotorcycleBrand;
use NiekNijland\Marktplaats\Data\MotorcycleSearchQuery;
use NiekNijland\Marktplaats\Data\SearchQuery;
use NiekNijland\Marktplaats\Exception\ClientException;
use PHPUnit\Framework\TestCase;

class SearchQueryTest extends TestCase
{
    public function test_default_query_builds_valid_url(): void
    {
        $query = new SearchQuery;
        $url = $query->buildUrl();

        $this->assertStringStartsWith('https://www.marktplaats.nl/lrp/api/search?', $url);
        $this->assertStringContainsString('limit=100', $url);
        $this->assertStringContainsString('offset=0', $url);
        $this->assertStringContainsString('sortBy=SORT_INDEX', $url);
        $this->assertStringContainsString('sortOrder=DECREASING', $url);
        $this->assertStringContainsString('searchInTitleAndDescription=true', $url);
        $this->assertStringContainsString('viewOptions=gallery-view', $url);
    }

    public function test_query_with_search_term(): void
    {
        $query = new SearchQuery(query: 'honda cbr');
        $url = $query->buildUrl();

        $this->assertStringContainsString('query=honda+cbr', $url);
    }

    public function test_query_with_category_ids(): void
    {
        $query = new SearchQuery(l1CategoryId: 678, l2CategoryId: 696);
        $url = $query->buildUrl();

        $this->assertStringContainsString('l1CategoryId=678', $url);
        $this->assertStringContainsString('l2CategoryId=696', $url);
    }

    public function test_query_omits_null_category_ids(): void
    {
        $query = new SearchQuery;
        $params = $query->toQueryParams();

        $this->assertArrayNotHasKey('l1CategoryId', $params);
        $this->assertArrayNotHasKey('l2CategoryId', $params);
        $this->assertArrayNotHasKey('query', $params);
    }

    public function test_limit_below_one_throws(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('limit must be at least 1');

        new SearchQuery(limit: 0);
    }

    public function test_limit_above_100_throws(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('limit must not exceed 100');

        new SearchQuery(limit: 101);
    }

    public function test_negative_offset_throws(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('offset must not be negative');

        new SearchQuery(offset: -1);
    }

    public function test_l2_without_l1_throws(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('l2CategoryId requires l1CategoryId');

        new SearchQuery(l2CategoryId: 696);
    }

    public function test_with_offset_creates_new_instance(): void
    {
        $original = new SearchQuery(query: 'test', limit: 50, offset: 0);
        $modified = $original->withOffset(50);

        $this->assertSame(0, $original->offset);
        $this->assertSame(50, $modified->offset);
        $this->assertSame('test', $modified->query);
        $this->assertSame(50, $modified->limit);
    }

    public function test_cache_key_is_deterministic(): void
    {
        $query1 = new SearchQuery(query: 'test', l1CategoryId: 678);
        $query2 = new SearchQuery(query: 'test', l1CategoryId: 678);

        $this->assertSame($query1->buildCacheKey(), $query2->buildCacheKey());
    }

    public function test_cache_key_differs_for_different_params(): void
    {
        $query1 = new SearchQuery(query: 'honda');
        $query2 = new SearchQuery(query: 'yamaha');

        $this->assertNotSame($query1->buildCacheKey(), $query2->buildCacheKey());
    }

    public function test_cache_key_starts_with_prefix(): void
    {
        $query = new SearchQuery;
        $key = $query->buildCacheKey();

        $this->assertStringStartsWith('marktplaats:search:', $key);
    }

    public function test_motorcycle_query_defaults(): void
    {
        $query = new MotorcycleSearchQuery;

        $this->assertSame(678, $query->l1CategoryId);
        $this->assertNull($query->l2CategoryId);
        $this->assertSame(100, $query->limit);
        $this->assertTrue($query->strictMode);
        $this->assertNull($query->brand);
    }

    public function test_motorcycle_query_with_brand(): void
    {
        $brand = new MotorcycleBrand(
            categoryId: 696,
            key: 'honda',
            name: 'Honda',
            fullName: 'Motoren | Honda',
            parentCategoryId: 678,
        );

        $query = new MotorcycleSearchQuery(brand: $brand);

        $this->assertSame(678, $query->l1CategoryId);
        $this->assertSame(696, $query->l2CategoryId);
        $this->assertSame($brand, $query->brand);
    }

    public function test_motorcycle_query_with_offset(): void
    {
        $brand = new MotorcycleBrand(
            categoryId: 696,
            key: 'honda',
            name: 'Honda',
            fullName: 'Motoren | Honda',
            parentCategoryId: 678,
        );

        $original = new MotorcycleSearchQuery(brand: $brand, strictMode: false);
        $modified = $original->withOffset(100);

        $this->assertSame(0, $original->offset);
        $this->assertSame(100, $modified->offset);
        $this->assertSame($brand, $modified->brand);
        $this->assertFalse($modified->strictMode);
    }

    public function test_motorcycle_query_url_includes_motorcycle_category(): void
    {
        $query = new MotorcycleSearchQuery;
        $url = $query->buildUrl();

        $this->assertStringContainsString('l1CategoryId=678', $url);
    }

    public function test_custom_sort_options(): void
    {
        $query = new SearchQuery(
            sortBy: SortBy::PRICE,
            sortOrder: SortOrder::INCREASING,
            viewOptions: ViewOptionKind::LIST_VIEW,
        );

        $url = $query->buildUrl();

        $this->assertStringContainsString('sortBy=PRICE', $url);
        $this->assertStringContainsString('sortOrder=INCREASING', $url);
        $this->assertStringContainsString('viewOptions=list-view', $url);
    }
}
