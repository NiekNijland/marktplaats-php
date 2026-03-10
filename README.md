# Marktplaats PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nieknijland/marktplaats-php.svg?style=flat-square)](https://packagist.org/packages/nieknijland/marktplaats-php)
[![Tests](https://img.shields.io/github/actions/workflow/status/nieknijland/marktplaats-php/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/nieknijland/marktplaats-php/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/nieknijland/marktplaats-php.svg?style=flat-square)](https://packagist.org/packages/nieknijland/marktplaats-php)

PHP client for fetching Marktplaats listings programmatically. Returns strongly typed immutable DTOs for search and listing detail pages.

## Installation

```bash
composer require nieknijland/marktplaats-php
```

## Quick Start

```php
use NiekNijland\Marktplaats\Client;
use NiekNijland\Marktplaats\Data\SearchQuery;

$client = new Client();

$result = $client->getSearch(new SearchQuery(
    query: 'bureau',
));

foreach ($result->listings as $listing) {
    echo $listing->title . ' - ' . $listing->priceInfo?->priceCents . ' cents' . PHP_EOL;
}

// Fetch a detail page
$detail = $client->getListing($listing->vipUrl);
echo $detail->description . PHP_EOL;
echo $detail->stats?->viewCount . ' views' . PHP_EOL;
```

## Documentation

- **[Usage](docs/usage.md)** — Search, filters, listing details, pagination, caching, custom HTTP client
- **[Testing](docs/testing.md)** — FakeClient, factories, assertions
- **[Caveats](docs/caveats.md)** — Keyword-stuffed titles, lease prices

## Development

```bash
composer test              # Unit tests
composer test-integration  # Integration tests (live API)
composer test-all          # Both suites
composer format            # Laravel Pint
composer analyse           # PHPStan level 8
composer codestyle         # rector + pint + analyse
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [NiekNijland](https://github.com/NiekNijland)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
