# Testing Utilities

The package ships test fakes and factories in `src/Testing/` for downstream consumers.

## FakeClient

`FakeClient` implements `ClientInterface` and lets you seed responses, throw exceptions, and assert method calls:

```php
use NiekNijland\Marktplaats\Testing\FakeClient;
use NiekNijland\Marktplaats\Testing\ListingDetailFactory;
use NiekNijland\Marktplaats\Testing\ListingFactory;
use NiekNijland\Marktplaats\Testing\SearchResultFactory;
use NiekNijland\Marktplaats\Data\SearchQuery;

$fake = new FakeClient();

// Seed search results
$fake->seedSearchResult(SearchResultFactory::make(
    listings: ListingFactory::makeMany(5),
    totalResultCount: 5,
));

$result = $fake->getSearch(new SearchQuery());
$fake->assertCalled('getSearch');
$fake->assertCalledTimes('getSearch', 1);

// Seed listing detail pages
$fake->seedListingDetail(ListingDetailFactory::make([
    'title' => 'Honda CBR 600RR',
]));

$detail = $fake->getListing('/v/motoren/honda/m123');
$fake->assertCalled('getListing');
```

Multiple seeded results are consumed in FIFO order. When exhausted, `getSearch` returns an empty `SearchResult`.

## Factories

All factories follow the same pattern: `make(array $overrides = [])` and `makeMany(int $count)`.

| Factory | Creates |
|---|---|
| `ListingFactory` | `Listing` (search result item) |
| `ListingDetailFactory` | `ListingDetail` (detail page) |
| `SearchResultFactory` | `SearchResult` (full search response) |
| `PriceInfoFactory` | `PriceInfo` |
| `LocationFactory` | `Location` |
| `SellerInformationFactory` | `SellerInformation` |
| `CategoryFactory` | `Category` |
| `CategoryCatalogFactory` | `CategoryCatalog` |

## Assertions

```php
$fake->assertCalled('getSearch');
$fake->assertNotCalled('getListing');
$fake->assertCalledTimes('getSearch', 2);
```

## Simulating Errors

```php
use NiekNijland\Marktplaats\Exception\ClientException;

$fake = new FakeClient();
$fake->shouldThrow(new ClientException('rate limited', 429));
```
