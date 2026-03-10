<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Tests;

use NiekNijland\Marktplaats\Data\AttributeByKey;
use NiekNijland\Marktplaats\Data\AttributeRange;
use NiekNijland\Marktplaats\Data\Enums\SortBy;
use NiekNijland\Marktplaats\Data\Enums\SortOrder;
use NiekNijland\Marktplaats\Data\Enums\ViewOptionKind;
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
        $query = new SearchQuery(categoryId: 678, subCategoryId: 696);
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
        $this->expectExceptionMessage('subCategoryId requires categoryId');

        new SearchQuery(subCategoryId: 696);
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
        $query1 = new SearchQuery(query: 'test', categoryId: 678);
        $query2 = new SearchQuery(query: 'test', categoryId: 678);

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

    public function test_query_with_postalcode(): void
    {
        $query = new SearchQuery(postalcode: '1234AB');
        $url = $query->buildUrl();

        $this->assertStringContainsString('postcode=1234AB', $url);
    }

    public function test_query_with_distance_meters(): void
    {
        $query = new SearchQuery(distanceMeters: 10000);
        $url = $query->buildUrl();

        $this->assertStringContainsString('distanceMeters=10000', $url);
    }

    public function test_query_with_offer_type(): void
    {
        $query = new SearchQuery(offerType: 'offered');
        $url = $query->buildUrl();

        $this->assertStringContainsString('offerType=offered', $url);
    }

    public function test_negative_distance_meters_throws(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('distanceMeters must not be negative');

        new SearchQuery(distanceMeters: -1);
    }

    public function test_query_omits_null_postalcode(): void
    {
        $query = new SearchQuery;
        $params = $query->toQueryParams();

        $this->assertArrayNotHasKey('postcode', $params);
    }

    public function test_query_with_attribute_ranges(): void
    {
        $query = new SearchQuery(
            attributeRanges: [
                new AttributeRange('PriceCents', 50000, 800000),
                new AttributeRange('constructionYear', 2016, 2024),
                new AttributeRange('mileage', 10000, 40000),
            ],
        );
        $url = $query->buildUrl();

        $this->assertStringContainsString('attributeRanges%5B%5D=PriceCents%3A50000%3A800000', $url);
        $this->assertStringContainsString('attributeRanges%5B%5D=constructionYear%3A2016%3A2024', $url);
        $this->assertStringContainsString('attributeRanges%5B%5D=mileage%3A10000%3A40000', $url);
    }

    public function test_query_with_attribute_range_open_ended(): void
    {
        $minOnly = new AttributeRange('PriceCents', from: 50000);
        $maxOnly = new AttributeRange('PriceCents', to: 800000);

        $this->assertSame('PriceCents:50000:', $minOnly->toString());
        $this->assertSame('PriceCents::800000', $maxOnly->toString());
    }

    public function test_query_with_attributes_by_id(): void
    {
        $query = new SearchQuery(
            attributesById: [98, 5225],
        );
        $url = $query->buildUrl();

        $this->assertStringContainsString('attributesById%5B%5D=98', $url);
        $this->assertStringContainsString('attributesById%5B%5D=5225', $url);
    }

    public function test_query_with_attributes_by_key(): void
    {
        $query = new SearchQuery(
            attributesByKey: [
                new AttributeByKey('offeredSince', 'Altijd'),
            ],
        );
        $url = $query->buildUrl();

        $this->assertStringContainsString('attributesByKey%5B%5D=offeredSince%3AAltijd', $url);
    }

    public function test_query_with_all_filter_params(): void
    {
        $query = new SearchQuery(
            query: 'sv 650',
            categoryId: 678,
            subCategoryId: 707,
            limit: 30,
            offset: 0,
            searchInTitleAndDescription: true,
            viewOptions: ViewOptionKind::LIST_VIEW,
            postalcode: '1234AB',
            distanceMeters: 10000,
            offerType: 'offered',
            attributeRanges: [
                new AttributeRange('PriceCents', 50000, 800000),
                new AttributeRange('constructionYear', 2016, 2024),
                new AttributeRange('mileage', 10000, 40000),
            ],
            attributesById: [98, 5225],
            attributesByKey: [
                new AttributeByKey('offeredSince', 'Altijd'),
            ],
        );
        $url = $query->buildUrl();

        $this->assertStringContainsString('query=sv+650', $url);
        $this->assertStringContainsString('l1CategoryId=678', $url);
        $this->assertStringContainsString('l2CategoryId=707', $url);
        $this->assertStringContainsString('limit=30', $url);
        $this->assertStringContainsString('postcode=1234AB', $url);
        $this->assertStringContainsString('distanceMeters=10000', $url);
        $this->assertStringContainsString('offerType=offered', $url);
        $this->assertStringContainsString('attributeRanges%5B%5D=PriceCents%3A50000%3A800000', $url);
        $this->assertStringContainsString('attributesById%5B%5D=98', $url);
        $this->assertStringContainsString('attributesByKey%5B%5D=offeredSince%3AAltijd', $url);
    }

    public function test_empty_array_params_omitted_from_url(): void
    {
        $query = new SearchQuery;
        $url = $query->buildUrl();

        $this->assertStringNotContainsString('attributeRanges', $url);
        $this->assertStringNotContainsString('attributesById', $url);
        $this->assertStringNotContainsString('attributesByKey', $url);
    }

    public function test_to_array_query_params_empty_by_default(): void
    {
        $query = new SearchQuery;

        $this->assertSame([], $query->toArrayQueryParams());
    }

    public function test_to_array_query_params_returns_formatted_values(): void
    {
        $query = new SearchQuery(
            attributeRanges: [new AttributeRange('PriceCents', 50000, 800000)],
            attributesById: [98],
            attributesByKey: [new AttributeByKey('offeredSince', 'Altijd')],
        );

        $arrayParams = $query->toArrayQueryParams();

        $this->assertSame(['PriceCents:50000:800000'], $arrayParams['attributeRanges']);
        $this->assertSame(['98'], $arrayParams['attributesById']);
        $this->assertSame(['offeredSince:Altijd'], $arrayParams['attributesByKey']);
    }

    public function test_cache_key_includes_filter_params(): void
    {
        $withFilters = new SearchQuery(
            attributeRanges: [new AttributeRange('PriceCents', 50000, 800000)],
        );
        $withoutFilters = new SearchQuery;

        $this->assertNotSame($withFilters->buildCacheKey(), $withoutFilters->buildCacheKey());
    }

    public function test_cache_key_deterministic_with_filter_params(): void
    {
        $query1 = new SearchQuery(
            attributeRanges: [
                new AttributeRange('PriceCents', 50000, 800000),
                new AttributeRange('mileage', 10000, 40000),
            ],
            attributesById: [98, 5225],
        );
        $query2 = new SearchQuery(
            attributeRanges: [
                new AttributeRange('PriceCents', 50000, 800000),
                new AttributeRange('mileage', 10000, 40000),
            ],
            attributesById: [98, 5225],
        );

        $this->assertSame($query1->buildCacheKey(), $query2->buildCacheKey());
    }

    public function test_with_offset_preserves_filter_params(): void
    {
        $original = new SearchQuery(
            postalcode: '1234AB',
            distanceMeters: 10000,
            offerType: 'offered',
            attributeRanges: [new AttributeRange('PriceCents', 50000, 800000)],
            attributesById: [98],
            attributesByKey: [new AttributeByKey('offeredSince', 'Altijd')],
        );

        $modified = $original->withOffset(30);

        $this->assertSame(30, $modified->offset);
        $this->assertSame('1234AB', $modified->postalcode);
        $this->assertSame(10000, $modified->distanceMeters);
        $this->assertSame('offered', $modified->offerType);
        $this->assertCount(1, $modified->attributeRanges);
        $this->assertSame('PriceCents', $modified->attributeRanges[0]->attribute);
        $this->assertSame([98], $modified->attributesById);
        $this->assertCount(1, $modified->attributesByKey);
        $this->assertSame('offeredSince', $modified->attributesByKey[0]->key);
    }

    public function test_with_offset_preserves_filter_params_and_category_ids(): void
    {
        $original = new SearchQuery(
            query: 'sv 650',
            categoryId: 678,
            postalcode: '1234AB',
            distanceMeters: 10000,
            offerType: 'offered',
            attributeRanges: [new AttributeRange('PriceCents', 50000, 800000)],
            attributesById: [98],
            attributesByKey: [new AttributeByKey('offeredSince', 'Altijd')],
        );

        $modified = $original->withOffset(30);

        $this->assertSame(30, $modified->offset);
        $this->assertSame('sv 650', $modified->query);
        $this->assertSame('1234AB', $modified->postalcode);
        $this->assertSame(10000, $modified->distanceMeters);
        $this->assertSame('offered', $modified->offerType);
        $this->assertSame(678, $modified->categoryId);
        $this->assertCount(1, $modified->attributeRanges);
        $this->assertSame([98], $modified->attributesById);
        $this->assertCount(1, $modified->attributesByKey);
    }

    public function test_attribute_range_to_array_and_from_array(): void
    {
        $range = new AttributeRange('PriceCents', 50000, 800000);
        $array = $range->toArray();

        $this->assertSame([
            'attribute' => 'PriceCents',
            'from' => 50000,
            'to' => 800000,
        ], $array);

        $restored = AttributeRange::fromArray($array);
        $this->assertSame('PriceCents', $restored->attribute);
        $this->assertSame(50000, $restored->from);
        $this->assertSame(800000, $restored->to);
    }

    public function test_attribute_by_key_to_array_and_from_array(): void
    {
        $attr = new AttributeByKey('offeredSince', 'Altijd');
        $array = $attr->toArray();

        $this->assertSame([
            'key' => 'offeredSince',
            'value' => 'Altijd',
        ], $array);

        $restored = AttributeByKey::fromArray($array);
        $this->assertSame('offeredSince', $restored->key);
        $this->assertSame('Altijd', $restored->value);
    }
}
