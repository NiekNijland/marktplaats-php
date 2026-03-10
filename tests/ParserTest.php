<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Tests;

use NiekNijland\Marktplaats\Data\Category;
use NiekNijland\Marktplaats\Data\Enums\PriceType;
use NiekNijland\Marktplaats\Data\Enums\SearchFacetType;
use NiekNijland\Marktplaats\Data\Enums\SortBy;
use NiekNijland\Marktplaats\Data\SearchResult;
use NiekNijland\Marktplaats\Exception\ClientException;
use NiekNijland\Marktplaats\Parser\SearchParser;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    private SearchParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SearchParser;
    }

    public function test_parse_motorcycle_search_response(): void
    {
        $json = $this->loadFixture('search-motorcycles.json');
        $result = $this->parser->parseJson($json);

        $this->assertSame(3, $result->totalResultCount);
        $this->assertSame(1, $result->maxAllowedPageNumber);
        $this->assertSame('test-correlation-id-123', $result->correlationId);
        $this->assertSame(678, $result->searchCategory);
        $this->assertCount(3, $result->listings);
        $this->assertCount(1, $result->topBlock);
        $this->assertCount(3, $result->facets);
    }

    public function test_parse_listing_fields(): void
    {
        $json = $this->loadFixture('search-motorcycles.json');
        $result = $this->parser->parseJson($json);
        $listing = $result->listings[0];

        $this->assertSame('m2100000001', $listing->itemId);
        $this->assertSame('Honda CBR600RR 2021', $listing->title);
        $this->assertSame(696, $listing->categoryId);
        $this->assertSame('/v/motoren/honda/m2100000001-honda-cbr600rr-2021', $listing->vipUrl);
        $this->assertSame('https://www.marktplaats.nl/v/motoren/honda/m2100000001-honda-cbr600rr-2021', $listing->fullUrl);
        $this->assertFalse($listing->reserved);
        $this->assertSame('DAGTOPPER', $listing->priorityProduct);
    }

    public function test_parse_listing_full_url_derivation(): void
    {
        $json = $this->loadFixture('search-motorcycles.json');
        $result = $this->parser->parseJson($json);

        foreach ($result->listings as $listing) {
            if ($listing->vipUrl !== null) {
                $this->assertStringStartsWith('https://www.marktplaats.nl/', $listing->fullUrl);
            }
        }
    }

    public function test_parse_price_info(): void
    {
        $json = $this->loadFixture('search-motorcycles.json');
        $result = $this->parser->parseJson($json);
        $listing = $result->listings[0];

        $this->assertNotNull($listing->priceInfo);
        $this->assertSame(899500, $listing->priceInfo->priceCents);
        $this->assertSame(PriceType::FIXED, $listing->priceInfo->priceType);
    }

    public function test_parse_location(): void
    {
        $json = $this->loadFixture('search-motorcycles.json');
        $result = $this->parser->parseJson($json);
        $listing = $result->listings[0];

        $this->assertNotNull($listing->location);
        $this->assertSame('Amsterdam', $listing->location->cityName);
        $this->assertSame('NL', $listing->location->countryAbbreviation);
        $this->assertFalse($listing->location->abroad);
    }

    public function test_parse_seller_information(): void
    {
        $json = $this->loadFixture('search-motorcycles.json');
        $result = $this->parser->parseJson($json);
        $listing = $result->listings[0];

        $this->assertNotNull($listing->sellerInformation);
        $this->assertSame(10001, $listing->sellerInformation->sellerId);
        $this->assertSame('MotorDealer Amsterdam', $listing->sellerInformation->sellerName);
        $this->assertTrue($listing->sellerInformation->isVerified);
    }

    public function test_parse_listing_attributes(): void
    {
        $json = $this->loadFixture('search-motorcycles.json');
        $result = $this->parser->parseJson($json);
        $listing = $result->listings[0];

        $this->assertCount(2, $listing->attributes);
        $this->assertSame('constructionYear', $listing->attributes[0]->key);
        $this->assertSame('2021', $listing->attributes[0]->value);

        $this->assertCount(1, $listing->extendedAttributes);
        $this->assertSame('engineDisplacement', $listing->extendedAttributes[0]->key);
    }

    public function test_parse_pictures(): void
    {
        $json = $this->loadFixture('search-motorcycles.json');
        $result = $this->parser->parseJson($json);
        $listing = $result->listings[0];

        $this->assertCount(1, $listing->pictures);
        $picture = $listing->pictures[0];
        $this->assertSame(1, $picture->id);
        $this->assertSame('01abc-1', $picture->mediaId);
        $this->assertNotNull($picture->aspectRatio);
        $this->assertSame(4, $picture->aspectRatio->width);
        $this->assertSame(3, $picture->aspectRatio->height);
    }

    public function test_parse_facets_category_tree(): void
    {
        $json = $this->loadFixture('search-motorcycles.json');
        $result = $this->parser->parseJson($json);

        $categoryFacet = $result->facets[0];
        $this->assertSame(SearchFacetType::CATEGORY_TREE, $categoryFacet->type);
        $this->assertCount(2, $categoryFacet->categories);
        $this->assertSame(696, $categoryFacet->categories[0]->id);
        $this->assertSame('Honda', $categoryFacet->categories[0]->label);
    }

    public function test_parse_facets_attribute_group(): void
    {
        $json = $this->loadFixture('search-motorcycles.json');
        $result = $this->parser->parseJson($json);

        $attrGroupFacet = $result->facets[2];
        $this->assertSame(SearchFacetType::ATTRIBUTE_GROUP, $attrGroupFacet->type);
        $this->assertCount(1, $attrGroupFacet->attributeGroup);
        $this->assertSame('honda', $attrGroupFacet->attributeGroup[0]->attributeValueKey);
    }

    public function test_parse_sort_options(): void
    {
        $json = $this->loadFixture('search-motorcycles.json');
        $result = $this->parser->parseJson($json);

        $this->assertCount(2, $result->sortOptions);
    }

    public function test_parse_search_category_options(): void
    {
        $json = $this->loadFixture('search-motorcycles.json');
        $result = $this->parser->parseJson($json);

        $this->assertNotEmpty($result->searchCategoryOptions);
        $honda = $result->searchCategoryOptions[0];
        $this->assertSame(696, $honda->id);
        $this->assertSame('honda', $honda->key);
        $this->assertSame('Motoren | Honda', $honda->fullName);
    }

    public function test_parse_search_request(): void
    {
        $json = $this->loadFixture('search-motorcycles.json');
        $result = $this->parser->parseJson($json);

        $this->assertNotNull($result->searchRequest);
        $this->assertSame(678, $result->searchRequest->categories->category?->id);
        $this->assertSame('motoren', $result->searchRequest->categories->category?->key);
        $this->assertSame(SortBy::SORT_INDEX, $result->searchRequest->sortOptions->sortBy);
    }

    public function test_parse_meta_tags(): void
    {
        $json = $this->loadFixture('search-motorcycles.json');
        $result = $this->parser->parseJson($json);

        $this->assertNotNull($result->metaTags);
        $this->assertSame('Motoren kopen | Marktplaats.nl', $result->metaTags->metaTitle);
    }

    public function test_parse_generic_search_with_highlights_and_trust_indicators(): void
    {
        $json = $this->loadFixture('search-generic-query.json');
        $result = $this->parser->parseJson($json);

        $listing = $result->listings[0];
        $this->assertCount(1, $listing->highlights);
        $this->assertSame('title', $listing->highlights[0]->key);
        $this->assertCount(1, $listing->trustIndicators);
        $this->assertSame('id_verified', $listing->trustIndicators[0]->key);
    }

    public function test_parse_empty_json_object(): void
    {
        $result = $this->parser->parseJson('{}');

        $this->assertSame(0, $result->totalResultCount);
        $this->assertSame([], $result->listings);
    }

    public function test_parse_invalid_json_throws(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Failed to decode JSON');

        $this->parser->parseJson('not valid json');
    }

    public function test_parse_json_with_unexpected_shapes_does_not_throw_type_error(): void
    {
        $json = json_encode([
            'listings' => ['invalid'],
            'topBlock' => 'invalid',
            'facets' => [123, ['id' => 'abc']],
            'searchCategoryOptions' => [null, ['id' => '696', 'key' => 'honda']],
            'searchRequest' => 'invalid',
            'metaTags' => 'invalid',
        ], JSON_THROW_ON_ERROR);

        $result = $this->parser->parseJson($json);

        $this->assertSame([], $result->listings);
        $this->assertSame([], $result->topBlock);
        $this->assertCount(1, $result->facets);
        $this->assertCount(1, $result->searchCategoryOptions);
        $this->assertNull($result->searchRequest);
        $this->assertNull($result->metaTags);
    }

    public function test_parse_category_catalog(): void
    {
        $json = $this->loadFixture('search-motorcycle-brand-catalog.json');
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $catalog = $this->parser->parseCategoryCatalog($data, 678);

        $categoryNames = array_map(fn (Category $category): ?string => $category->name, $catalog->categories);

        $this->assertContains('Honda', $categoryNames);
        $this->assertContains('BMW', $categoryNames);
        $this->assertContains('Yamaha', $categoryNames);
        $this->assertContains('Oldtimers', $categoryNames);
        $this->assertContains('Schademotoren', $categoryNames);
        $this->assertContains('Motorkleding', $categoryNames);
    }

    public function test_parse_category_catalog_fields(): void
    {
        $json = $this->loadFixture('search-motorcycle-brand-catalog.json');
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $catalog = $this->parser->parseCategoryCatalog($data, 678);

        $honda = null;

        foreach ($catalog->categories as $category) {
            if ($category->key === 'honda') {
                $honda = $category;
                break;
            }
        }

        $this->assertNotNull($honda);
        $this->assertSame(696, $honda->id);
        $this->assertSame('Honda', $honda->name);
        $this->assertSame('Motoren | Honda', $honda->fullName);
        $this->assertSame(678, $honda->parentId);
    }

    public function test_to_array_from_array_roundtrip(): void
    {
        $json = $this->loadFixture('search-motorcycles.json');
        $original = $this->parser->parseJson($json);

        $array = $original->toArray();
        $restored = SearchResult::fromArray($array);

        $this->assertSame($original->totalResultCount, $restored->totalResultCount);
        $this->assertSame($original->correlationId, $restored->correlationId);
        $this->assertCount(count($original->listings), $restored->listings);
        $this->assertSame($original->listings[0]->itemId, $restored->listings[0]->itemId);
        $this->assertSame($original->listings[0]->fullUrl, $restored->listings[0]->fullUrl);
    }

    private function loadFixture(string $filename): string
    {
        $path = __DIR__.'/Fixtures/'.$filename;
        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }
}
