# Changelog

All notable changes to `marktplaats-php` will be documented in this file.

## Unreleased

### Changed

- Fixed quick-start docs to use the correct `SearchQuery` named arguments.
- Added `OfferType` enum and typed `SearchQuery::offerType`.
- Made `resetSession()` clear stored session cookies.
- Added optional retry/backoff and timeout configuration in `Client`.
- Hardened parser input normalization for malformed payload resilience.
- Refactored cache helpers in `Client` to reduce duplication.
- Applied excluded category filtering to both search `listings` and `topBlock`.
- Reworked listing-detail parsing to use DOM-based extraction for description/attributes.
- Added shared URL resolver utility.
- Renamed query field `postalcode` to `postalCode`.
- Added typed enums for listing ad type, seller type, and facet type.
- Added deduplication in `getSearchAll()` for unstable pagination overlap.
- Hardened `fromArray()` hydration for cached/malformed payload safety.
- Generalized docs and shipped testing factory defaults beyond motorcycle-specific examples.

### Tooling

- Updated Composer platform support to `php:^8.3`.
- Removed unused `spatie/ray` dev dependency.
- Made `composer test` commands run with `--no-coverage` by default.

### Breaking

- `SearchQuery::$offerType` now expects `?OfferType` instead of `?string`.
- `SearchQuery` and `SearchQueryBuilder` now use `postalCode` instead of `postalcode`.
- `ListingDetail::$adType` now uses `?ListingAdType` and keeps unknown values in `rawAdType`.
- `ListingDetailSeller::$sellerType` now uses `?SellerType` and keeps unknown values in `rawSellerType`.
- `SearchFacet::$type` now uses `?SearchFacetType` and keeps unknown values in `rawType`.
