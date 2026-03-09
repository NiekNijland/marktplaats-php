# AGENTS.md

Instructions for AI coding agents operating in this repository.

## Build & Test Commands

```bash
composer test                # Unit tests only (default, no live HTTP)
composer test-integration    # Integration tests only (hits live Marktplaats API)
composer test-all            # Both suites
composer test-coverage       # Unit tests with coverage reports

composer analyse             # PHPStan level 8 (src/ only)
composer format              # Laravel Pint code formatting
composer rector              # Rector automated refactoring
composer rector:dry-run      # Rector dry run (no changes)
composer codestyle           # rector + pint + analyse (all quality checks)
```

### Running a single test

```bash
vendor/bin/phpunit --filter test_method_name
vendor/bin/phpunit --filter ClassName
vendor/bin/phpunit --filter ClassName::test_method_name
vendor/bin/phpunit tests/ParserTest.php
```

### After changing code, always run

1. `composer test` — all 76 unit tests must pass
2. `composer analyse` — PHPStan level 8 must report zero errors

## Architecture

See `BLUEPRINT.md` for the full reference. Key rules:

- **Interface-first**: `ClientInterface` defines the public API. Both `Client` and `FakeClient` implement it.
- **Immutable DTOs**: All data classes are `readonly class` with promoted constructor properties.
- **Parser separated from transport**: `Client` handles HTTP; `SearchParser` handles JSON-to-DTO mapping.
- **Single exception**: All operational errors throw `ClientException` (extends `RuntimeException`).
- **Shipped test utilities**: `src/Testing/` contains `FakeClient`, `RecordedCall`, and factories for downstream consumers.
- **No framework coupling**: Standalone Composer library. PSR-18 HTTP client, PSR-16 cache.

## Code Style

### File structure

Every PHP file must start with:

```php
<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\...;
```

### Formatting

- Formatter: **Laravel Pint** (default `laravel` preset, no `pint.json`)
- 4-space indentation, LF line endings, UTF-8
- Negation with space: `! $condition`
- Trailing commas on multiline calls and arrays
- Opening brace on same line for classes and methods

### Imports

- Fully qualified `use` statements at the top of every file — no inline `\Namespace\Class` references
- Ordering: PHP built-ins first, then vendor, then project classes
- One class per `use` statement

### Types

- Native PHP types on **all** parameters and return types — no exceptions
- PHPDoc only for array shapes (`@param array<string, mixed>`) and generic types (`@return Generator<int, Listing>`)
- Nullable types use `?Type` syntax
- Enums are string-backed

### Naming

| Element | Convention | Examples |
|---------|-----------|----------|
| Classes | PascalCase | `SearchResult`, `ListingFactory` |
| Public methods | `get*` prefix | `getSearch()`, `getMotorcycleBrandCatalog()` |
| Private methods | Verb prefix | `fetchRawResponse()`, `parseListing()`, `applyStrictMotorcycleFilter()` |
| Static factories | `make()`, `fromArray()`, `empty()` | `ListingFactory::make()`, `Listing::fromArray()` |
| Properties | camelCase | `$cacheTtl`, `$totalResultCount` |
| Constants | UPPER_SNAKE_CASE, typed | `private const string BASE_URL` |
| Test methods | `test_` snake_case | `test_search_returns_search_result()` |
| Enums cases | UPPER_SNAKE_CASE | `PriceType::FIXED` |

### Classes

- DTOs: `readonly class` with constructor property promotion, `toArray()`, and `static fromArray()`
- Non-DTOs (Client, Parser, Factories): regular `class` — NOT `readonly`, NOT `final`
- **Never use `final`** — no class in this codebase is `final`
- **Use `readonly` on DTO classes** — all Data classes and `RecordedCall` are `readonly class`
- No abstract classes, no traits

### Error handling

- Single exception: `NiekNijland\Marktplaats\Exception\ClientException`
- Always chain previous exception: `throw new ClientException('msg', 0, $e)`
- HTTP status code passed as `$code`: `throw new ClientException('msg', $statusCode)`
- Cache failures are silently ignored (catch and return null / no-op)
- Query validation errors thrown in constructors

### DTO pattern

```php
readonly class ExampleDto
{
    public function __construct(
        public string $field,
        public ?string $optional,
    ) {}

    /** @return array{field: string, optional: ?string} */
    public function toArray(): array { /* ... */ }

    /** @param array{field?: string, optional?: ?string} $data */
    public static function fromArray(array $data): self { /* ... */ }
}
```

## Testing Conventions

- Extend `PHPUnit\Framework\TestCase`
- Prefer `assertSame()` over `assertEquals()` (strict type checking)
- Mock HTTP with Guzzle `MockHandler` + `HandlerStack` — zero real HTTP in unit tests
- Load fixtures via `file_get_contents()` from `tests/Fixtures/`
- Test namespace mirrors src: `NiekNijland\Marktplaats\Tests\`
- Integration tests in `tests/Integration/` — excluded from `composer test`

### Factory pattern (src/Testing/)

- Static `make()` with named parameters and sensible defaults
- `makeMany(int $count)` for collections
- Factories are in `src/Testing/` (shipped to consumers), not `tests/`

## Key Decisions

- Brand category IDs are **never hardcoded** — discovered live via `getMotorcycleBrandCatalog()`
- `fullUrl` is derived by the parser from `vipUrl` at parse time, not a computed property
- `getSearchAll()` bypasses cache (fetches live) to avoid stale pagination
- Motorcycle strict mode is client-side post-fetch filtering (API doesn't support exclusions)
- `toArray()`/`fromArray()` symmetry is required on all DTOs for cache serialization
