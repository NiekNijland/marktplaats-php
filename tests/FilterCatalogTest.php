<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Tests;

use NiekNijland\Marktplaats\Data\Enums\SearchFacetType;
use NiekNijland\Marktplaats\Data\FilterCatalog;
use NiekNijland\Marktplaats\Testing\FilterCatalogFactory;
use PHPUnit\Framework\TestCase;

class FilterCatalogTest extends TestCase
{
    public function test_find_by_key_returns_matching_facet(): void
    {
        $catalog = FilterCatalogFactory::make();

        $facet = $catalog->findByKey('brand');

        $this->assertNotNull($facet);
        $this->assertSame(SearchFacetType::ATTRIBUTE_GROUP, $facet->type);
    }

    public function test_get_range_and_group_facets(): void
    {
        $catalog = FilterCatalogFactory::make();

        $this->assertCount(1, $catalog->getRangeFacets());
        $this->assertCount(1, $catalog->getGroupFacets());
    }

    public function test_to_array_from_array_roundtrip(): void
    {
        $original = FilterCatalogFactory::make();
        $array = $original->toArray();
        $restored = FilterCatalog::fromArray($array);

        $this->assertSame($original->categoryId, $restored->categoryId);
        $this->assertSame($original->subCategoryId, $restored->subCategoryId);
        $this->assertCount(count($original->facets), $restored->facets);
        $this->assertSame($original->facets[0]->key, $restored->facets[0]->key);
    }
}
