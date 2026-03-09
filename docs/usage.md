# Usage

## Basic Search

```php
use NiekNijland\Marktplaats\Client;
use NiekNijland\Marktplaats\Data\SearchQuery;

$client = new Client();

$result = $client->getSearch(new SearchQuery(
    query: 'honda cbr',
    l1CategoryId: 678,
    limit: 25,
));

foreach ($result->listings as $listing) {
    echo $listing->title . PHP_EOL;
    echo $listing->fullUrl . PHP_EOL;
    echo $listing->priceInfo?->priceCents . ' cents' . PHP_EOL;
}
```

## Category Search with Catalog Discovery

Subcategory IDs are never hardcoded. Discover them live from the API metadata:

```php
use NiekNijland\Marktplaats\Client;
use NiekNijland\Marktplaats\Data\SearchQuery;

$client = new Client();

$catalog = $client->getCategoryCatalog(678); // Motoren

$honda = $catalog->findByName('Honda');

$result = $client->getSearch(new SearchQuery(
    l1CategoryId: 678,
    l2CategoryId: $honda?->id,
    excludedCategoryIds: [723, 724], // optional client-side exclusions
));

foreach ($result->listings as $listing) {
    echo $listing->title . PHP_EOL;
}
```

## Paginate Through All Results

```php
use NiekNijland\Marktplaats\Client;
use NiekNijland\Marktplaats\Data\SearchQuery;

$client = new Client();

foreach ($client->getSearchAll(new SearchQuery(l1CategoryId: 678)) as $listing) {
    echo $listing->itemId . ': ' . $listing->title . PHP_EOL;
}
```

`getSearchAll` yields listings page by page and stops automatically when results are exhausted.

## Search with Filters

Use `AttributeRange` and `AttributeByKey` to apply price ranges, mileage filters, and other attribute-based filters:

```php
use NiekNijland\Marktplaats\Client;
use NiekNijland\Marktplaats\Data\AttributeByKey;
use NiekNijland\Marktplaats\Data\AttributeRange;
use NiekNijland\Marktplaats\Data\SearchQuery;

$client = new Client();

$result = $client->getSearch(new SearchQuery(
    l1CategoryId: 678,
    postcode: '1012AB',
    attributeRanges: [
        new AttributeRange(attribute: 'PriceCents', from: 50000, to: 800000),
    ],
    attributesByKey: [
        new AttributeByKey(key: 'fuel', value: 'benzine'),
    ],
));
```

## Listing Detail Page

Fetch the full detail page for a single listing, including description, attributes, bid history, and seller information:

```php
use NiekNijland\Marktplaats\Client;

$client = new Client();

$detail = $client->getListing('https://www.marktplaats.nl/v/motoren/honda/m1234567890-test');

echo $detail->title . PHP_EOL;
echo $detail->description . PHP_EOL;
echo $detail->priceInfo?->priceCents . ' cents' . PHP_EOL;
echo $detail->seller?->name . PHP_EOL;
echo $detail->seller?->location?->cityName . PHP_EOL;
echo $detail->stats?->viewCount . ' views' . PHP_EOL;

foreach ($detail->attributes as $attr) {
    echo $attr->label . ': ' . $attr->value . PHP_EOL;
}

if ($detail->bidsInfo?->isBiddingEnabled) {
    foreach ($detail->bidsInfo->bids as $bid) {
        echo $bid->user?->nickname . ' bid ' . $bid->valueCents . ' cents' . PHP_EOL;
    }
}
```

You can also pass a relative `vipUrl` as returned in search result listings:

```php
$listing = $result->listings[0];
$detail = $client->getListing($listing->vipUrl);
```

## Caching

Inject any PSR-16 cache to avoid redundant API calls. Caching applies to search results, category catalog discovery, and listing detail pages:

```php
use NiekNijland\Marktplaats\Client;

$client = new Client(
    cache: $yourPsr16Cache,
    cacheTtl: 1800, // 30 minutes
);
```

Note: `getSearchAll()` always fetches live (bypasses cache) to avoid stale pagination metadata.

## Custom HTTP Client

The client accepts any PSR-18 HTTP client:

```php
use GuzzleHttp\Client as GuzzleClient;
use NiekNijland\Marktplaats\Client;

$client = new Client(
    httpClient: new GuzzleClient([
        'timeout' => 10,
    ]),
);
```
