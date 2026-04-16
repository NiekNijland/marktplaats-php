# Changelog

All notable changes to `marktplaats-php` will be documented in this file.

## v0.5.0 - 2026-04-16

### Changed

- Split `getStats()` failure tracking into transport-level (`failures`) and content-level (`content_failures`) counters. HTTP 4xx responses from Marktplaats (403 auth errors, 404/410 missing listings, 429 rate limits, 400 bad query) now increment `content_failures`; only HTTP 5xx and network / redirect errors increment `failures`. Callers that feed these stats into proxy-health tracking can now distinguish "this request was rejected by Marktplaats" from "the transport itself misbehaved".

### Breaking

- The `getStats()` return shape now includes a new `content_failures` field. Callers asserting on the exact array shape (e.g. strict equality against the whole stats array) need to be updated.

## v0.4.2 - 2026-04-04

### Summary

- detect expired Marktplaats detail pages even when the response status is 200
- throw GoneException for verlopen detail shells so downstream apps can mark listings unavailable
- add regression coverage for parser and client expired-page handling

## v0.4.1 - 2026-03-27

### Changed

- Follow HTTP redirects for listing detail requests, including relative `Location` headers.
- Added redirect coverage for `Client::getListing()` to prevent canonical Marktplaats listing URLs from failing on `301` responses.

## v0.4.0 - 2026-03-26

### Added

- `NotFoundException` thrown on HTTP 404 responses (extends `ClientException`)
- `GoneException` thrown on HTTP 410 responses (extends `NotFoundException`)

Both are subtypes of `ClientException`, so existing catch blocks remain compatible.

## v0.2.0 - 2026-03-10

Improved query structure. cleanup.

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
