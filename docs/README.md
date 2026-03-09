# Marktplaats PHP v1 Plan Documentation

This folder contains the complete implementation plan for `marktplaats-php` v1.

The plan is based on:

- Your explicit product decisions for v1.
- Existing behavior in `mp-scraper`.
- Architecture and conventions from `viabovag-php`.
- Live API behavior observed from `https://www.marktplaats.nl/lrp/api/search`.

## v1 Decisions Locked In

- Scope is search only (including listing links), no full VIP detail scraping yet.
- DTO-only public API, no raw payload exposure.
- Precise payload boundaries use DTOs, not `array<string, mixed>` signatures.
- Collect as many listing/search fields as realistically available in v1.
- Motorcycle-first defaults now, but package architecture must support all listing types later.
- Brand category codes (for included brands) are never hardcoded; they are fetched live via package methods.
- PHP version remains `^8.4`.
- Blueprint architecture is followed fully now, including tooling and tests.
- Caching is optional and active only when cache is injected.
- No retries/backoff in v1.
- Default test run uses local fixtures only; live site testing is separate.

## Document Map

- `docs/01-v1-scope-and-goals.md`
- `docs/02-current-state-and-gap-analysis.md`
- `docs/03-marktplaats-api-research.md`
- `docs/04-architecture-and-project-layout.md`
- `docs/05-public-api-contract-proposal.md`
- `docs/06-dto-enum-catalog.md`
- `docs/07-query-building-and-motorcycle-filtering.md`
- `docs/08-caching-error-handling-and-runtime-behavior.md`
- `docs/09-testing-strategy-and-fixtures.md`
- `docs/10-tooling-and-ci-plan.md`
- `docs/11-implementation-roadmap.md`
- `docs/12-risks-assumptions-and-future-evolution.md`
- `docs/13-api-schema-appendix.md`
- `docs/14-file-by-file-checklist.md`
- `docs/15-brand-discovery-policy.md`

## How To Use This Plan

Read in order from 01 to 15.

- 01-03 define what must be built and why.
- 04-08 define technical shape and runtime behavior.
- 09-10 define quality gates and automation.
- 11 defines delivery order.
- 12 captures risk, assumptions, and future expansion path.
- 13-14 provide schema reference and implementation checklist.
- 15 defines the no-hardcoded-brand-codes policy.
