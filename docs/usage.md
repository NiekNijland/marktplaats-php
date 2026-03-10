# Usage

## Basic Search

```php
use NiekNijland\Marktplaats\Client;
use NiekNijland\Marktplaats\Data\Enums\OfferType;
use NiekNijland\Marktplaats\Data\SearchQuery;

$client = new Client();

$result = $client->getSearch(new SearchQuery(
    query: 'bureau',
    categoryId: 678,
    distanceMeters: 25000,
    offerType: OfferType::OFFERED,
    limit: 25,
));

foreach ($result->listings as $listing) {
    echo $listing->title . PHP_EOL;
    echo $listing->fullUrl . PHP_EOL;
    echo $listing->priceInfo?->priceCents . ' cents' . PHP_EOL;
}
```

## Fluent Query Builder

If you prefer a fluent API over a large constructor, use `SearchQuery::builder()`:

```php
use NiekNijland\Marktplaats\Data\AttributeByKey;
use NiekNijland\Marktplaats\Data\AttributeRange;
use NiekNijland\Marktplaats\Data\Enums\OfferType;
use NiekNijland\Marktplaats\Data\SearchQuery;

$query = SearchQuery::builder()
    ->query('bureau')
    ->categoryId(678)
    ->subCategoryId(696)
    ->postalCode('1012AB')
    ->distanceMeters(25000)
    ->offerType(OfferType::OFFERED)
    ->addAttributeRange(new AttributeRange('PriceCents', 50000, 800000))
    ->addAttributeByKey(new AttributeByKey('fuel', 'benzine'))
    ->build();

$result = $client->getSearch($query);
```

## Category Search with Catalog Discovery

Subcategory IDs are never hardcoded. Discover them live from the API metadata:

```php
use NiekNijland\Marktplaats\Client;
use NiekNijland\Marktplaats\Data\SearchQuery;

$client = new Client();

$catalog = $client->getCategoryCatalog(678);

$matchedCategory = $catalog->categories[0] ?? null;

$result = $client->getSearch(
    new SearchQuery(
        categoryId: 678,
        subCategoryId: $matchedCategory?->id,
    ),
    excludedCategoryIds: [723, 724], // optional client-side exclusions
);

foreach ($result->listings as $listing) {
    echo $listing->title . PHP_EOL;
}
```

## Paginate Through All Results

```php
use NiekNijland\Marktplaats\Client;
use NiekNijland\Marktplaats\Data\SearchQuery;

$client = new Client();

foreach ($client->getSearchAll(new SearchQuery(categoryId: 678)) as $listing) {
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
    categoryId: 678,
    postalCode: '1012AB',
    attributeRanges: [
        new AttributeRange(attribute: 'PriceCents', from: 50000, to: 800000),
    ],
    attributesByKey: [
        new AttributeByKey(key: 'fuel', value: 'benzine'),
    ],
));
```

## Discover Available Filters

Use filter discovery to inspect available range and option filters for a category before building a query:

```php
use NiekNijland\Marktplaats\Client;

$client = new Client();
$catalog = $client->getFilterCatalog(678);

foreach ($catalog->getRangeFacets() as $facet) {
    echo $facet->key . PHP_EOL;
}

$brandFacet = $catalog->findByKey('brand');
if ($brandFacet !== null) {
    foreach ($brandFacet->attributeGroup as $option) {
        echo $option->attributeValueKey . ': ' . $option->attributeValueLabel . PHP_EOL;
    }
}
```

## Listing Detail Page

Fetch the full detail page for a single listing, including description, attributes, bid history, and seller information:

```php
use NiekNijland\Marktplaats\Client;

$client = new Client();

$detail = $client->getListing('https://www.marktplaats.nl/v/huis-en-inrichting/bureaus/m1234567890-test');

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

// Build a sized image URL from detail data (uses gallery imageSizes)
echo $detail->getImageUrl(0, 'XL') . PHP_EOL;
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

## Timeouts and Retries

When you use the default internal Guzzle client, you can configure request timeout and optional retry/backoff:

```php
use NiekNijland\Marktplaats\Client;

$client = new Client(
    requestTimeoutSeconds: 10.0,
    maxRetries: 2,
    retryDelayMilliseconds: 200,
);
```

Retries apply to transport failures and HTTP `429`/`5xx` responses.

## Session Reset

The client keeps cookies returned by Marktplaats for follow-up requests. You can clear those cookies explicitly:

```php
$client->resetSession();
```

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
