# Caveats

## Keyword-stuffed titles

Marktplaats search is text-based. Sellers frequently add related model numbers to titles to appear in more searches (e.g. a GSX-R 600 listed as `"SUZUKI GSX-R 600 SCORPION GSXR GSXR600 750 1000"`). The package returns exactly what the API returns.

To verify actual engine displacement, check:

- **Search results**: the `engineDisplacement` extended attribute (present on some listings)
- **Detail pages**: the `Motorinhoud` attribute (e.g. `"599 cc"`, `"999 cc"`)

```php
// Filter search results by engine displacement
$filtered = array_filter($result->listings, function ($listing) {
    foreach ($listing->extendedAttributes as $attr) {
        if ($attr->key === 'engineDisplacement') {
            return str_contains($attr->value, '999') || str_contains($attr->value, '998');
        }
    }
    return true; // keep listings without displacement info
});

// Or verify via detail page
$detail = $client->getListing($listing->vipUrl);
foreach ($detail->attributes as $attr) {
    if ($attr->label === 'Motorinhoud') {
        echo $attr->value; // "999 cc"
    }
}
```

## Lease prices in results

Some dealer listings show monthly lease amounts instead of the full sale price while using `priceType: FIXED`. For example, a motorcycle may appear with `priceCents: 16075` (EUR 160.75/month) rather than the actual vehicle price.

These listings are typically recognizable by:

- Unusually low price relative to the vehicle category
- Lease-related keywords in the title or description (`"lease"`, `"p/m"`, `"per maand"`)
- Seller type `TRADER` (dealer)

The package returns raw API data without modification. Apply your own filtering logic if needed:

```php
foreach ($result->listings as $listing) {
    $priceCents = $listing->priceInfo?->priceCents ?? 0;
    $isLikelyLease = $priceCents > 0
        && $priceCents < 100000 // under EUR 1000
        && stripos($listing->title, 'lease') !== false;

    if ($isLikelyLease) {
        continue; // skip probable lease listings
    }
}
```
