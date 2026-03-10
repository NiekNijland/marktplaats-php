<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Tests\Integration;

use NiekNijland\Marktplaats\Client;
use NiekNijland\Marktplaats\Data\Category;
use NiekNijland\Marktplaats\Data\SearchQuery;
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    public function test_live_search_returns_parseable_result(): void
    {
        $client = new Client;
        $query = new SearchQuery(
            categoryId: 678,
            limit: 5,
        );

        $result = $client->getSearch($query);

        $this->assertGreaterThanOrEqual(0, $result->totalResultCount);
        $this->assertNotEmpty($result->listings);

        foreach ($result->listings as $listing) {
            $this->assertNotEmpty($listing->itemId);
            $this->assertNotEmpty($listing->title);

            if ($listing->vipUrl !== null) {
                $this->assertNotNull($listing->fullUrl);
                $this->assertStringStartsWith('https://www.marktplaats.nl/', $listing->fullUrl);
            }
        }
    }

    public function test_live_search_with_excluded_categories(): void
    {
        $client = new Client;
        $query = new SearchQuery(
            categoryId: 678,
            limit: 5,
        );
        $result = $client->getSearch($query, [723, 724]);

        $this->assertGreaterThanOrEqual(0, $result->totalResultCount);
    }

    public function test_live_category_catalog_discovery(): void
    {
        $client = new Client;
        $catalog = $client->getCategoryCatalog(678);

        $this->assertSame(678, $catalog->parentCategoryId);
        $this->assertNotEmpty($catalog->categories);

        $categoryNames = array_map(fn (Category $category): ?string => $category->name, $catalog->categories);
        $this->assertContains('Honda', $categoryNames);
        $this->assertContains('Yamaha', $categoryNames);
    }

    public function test_live_filter_catalog_discovery(): void
    {
        $client = new Client;
        $catalog = $client->getFilterCatalog(678);

        $this->assertSame(678, $catalog->categoryId);
        $this->assertNotEmpty($catalog->facets);
    }
}
