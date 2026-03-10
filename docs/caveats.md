# Caveats

## Keyword-stuffed titles

Marktplaats search is text-based. Sellers frequently add loosely related terms and model numbers to titles to appear in more searches. The package returns exactly what the API returns.

If you need stricter matching, prefer validating with listing attributes instead of title text.

```php
// Filter search results by brand attribute instead of title text
$filtered = array_filter($result->listings, function ($listing) {
    foreach ($listing->extendedAttributes as $attr) {
        if ($attr->key === 'brand') {
            return strcasecmp($attr->value, 'IKEA') === 0;
        }
    }
    return true; // keep listings without brand info
});

// Or verify via detail page attributes
$detail = $client->getListing($listing->vipUrl);
foreach ($detail->attributes as $attr) {
    if ($attr->label === 'Merk') {
        echo $attr->value;
    }
}
```

## Installment-like prices in results

Some listings show monthly amounts instead of full item prices while still using `priceType: FIXED`.

These listings are typically recognizable by:

- Unusually low price relative to comparable listings
- Installment-related keywords in title or description (`"lease"`, `"p/m"`, `"per maand"`)
- Seller type `TRADER`

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
