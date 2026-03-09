# Marktplaats PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nieknijland/marktplaats-php.svg?style=flat-square)](https://packagist.org/packages/nieknijland/marktplaats-php)
[![Tests](https://img.shields.io/github/actions/workflow/status/nieknijland/marktplaats-php/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/nieknijland/marktplaats-php/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/nieknijland/marktplaats-php.svg?style=flat-square)](https://packagist.org/packages/nieknijland/marktplaats-php)

PHP client for fetching Marktplaats search listings programmatically. Returns strongly typed immutable DTOs. Motorcycle-first defaults with architecture that supports all listing categories.

## Installation

```bash
composer require nieknijland/marktplaats-php
```

## Usage

### Basic Search

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

### Motorcycle Search with Brand Discovery

Brand category IDs are never hardcoded. Discover them live from the API:

```php
use NiekNijland\Marktplaats\Client;
use NiekNijland\Marktplaats\Data\MotorcycleSearchQuery;

$client = new Client();

// Discover available brands from live API metadata
$catalog = $client->getMotorcycleBrandCatalog();

// Find Honda by key
$honda = null;
foreach ($catalog->brands as $brand) {
    if ($brand->key === 'honda') {
        $honda = $brand;
        break;
    }
}

// Search with the discovered brand
$result = $client->getMotorcycleSearch(new MotorcycleSearchQuery(
    brand: $honda,
));

// Strict mode (default) filters out non-bike categories like accessories
foreach ($result->listings as $listing) {
    echo $listing->title . PHP_EOL;
}
```

### Paginate Through All Results

```php
use NiekNijland\Marktplaats\Client;
use NiekNijland\Marktplaats\Data\MotorcycleSearchQuery;

$client = new Client();

foreach ($client->getSearchAll(new MotorcycleSearchQuery()) as $listing) {
    echo $listing->itemId . ': ' . $listing->title . PHP_EOL;
}
```

`getSearchAll` yields listings page by page. It skips `topBlock` listings (promoted duplicates) and stops automatically when results are exhausted.

### Optional Caching

Inject any PSR-16 cache to avoid redundant API calls:

```php
use NiekNijland\Marktplaats\Client;

$client = new Client(
    cache: $yourPsr16Cache,
    cacheTtl: 1800, // 30 minutes
);
```

### Custom HTTP Client

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

## Testing Utilities

The package ships test fakes and factories in `src/Testing/` for downstream consumers:

```php
use NiekNijland\Marktplaats\Testing\FakeClient;
use NiekNijland\Marktplaats\Testing\SearchResultFactory;
use NiekNijland\Marktplaats\Testing\ListingFactory;

$fake = new FakeClient();
$fake->seedSearchResult(SearchResultFactory::make(
    listings: ListingFactory::makeMany(5),
    totalResultCount: 5,
));

$result = $fake->getSearch(new SearchQuery());
$fake->assertCalled('getSearch');
$fake->assertCalledTimes('getSearch', 1);
```

## v1 Scope

- Search only (no VIP detail scraping)
- DTO-only public API (no raw payload exposure)
- Motorcycle-first defaults, generic architecture
- Optional PSR-16 caching
- No retries or backoff
- PHP ^8.4

See `docs/` for the full implementation plan.

## Development

```bash
composer test              # Unit tests
composer test-integration  # Integration tests (live API)
composer test-all          # Both suites
composer format            # Laravel Pint
composer analyse           # PHPStan level 8
composer rector            # Rector refactoring
composer codestyle         # rector + pint + analyse
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [NiekNijland](https://github.com/NiekNijland)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
