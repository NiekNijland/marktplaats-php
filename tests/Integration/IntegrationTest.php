<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Tests\Integration;

use NiekNijland\Marktplaats\Client;
use NiekNijland\Marktplaats\Data\MotorcycleSearchQuery;
use NiekNijland\Marktplaats\Data\SearchQuery;
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    public function test_live_search_returns_parseable_result(): void
    {
        $client = new Client;
        $query = new SearchQuery(
            l1CategoryId: 678,
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

    public function test_live_motorcycle_search(): void
    {
        $client = new Client;
        $query = new MotorcycleSearchQuery(limit: 5);
        $result = $client->getMotorcycleSearch($query);

        $this->assertGreaterThanOrEqual(0, $result->totalResultCount);
    }

    public function test_live_brand_catalog_discovery(): void
    {
        $client = new Client;
        $catalog = $client->getMotorcycleBrandCatalog();

        $this->assertSame(678, $catalog->sourceCategoryId);
        $this->assertNotEmpty($catalog->brands);

        $brandNames = array_map(fn ($b) => $b->name, $catalog->brands);
        $this->assertContains('Honda', $brandNames);
        $this->assertContains('Yamaha', $brandNames);
    }
}
