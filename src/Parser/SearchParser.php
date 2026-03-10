<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Parser;

use DateTimeImmutable;
use JsonException;
use NiekNijland\Marktplaats\Data\Category;
use NiekNijland\Marktplaats\Data\CategoryCatalog;
use NiekNijland\Marktplaats\Data\Enums\SearchFacetType;
use NiekNijland\Marktplaats\Data\Listing;
use NiekNijland\Marktplaats\Data\ListingAttribute;
use NiekNijland\Marktplaats\Data\ListingHighlight;
use NiekNijland\Marktplaats\Data\ListingPicture;
use NiekNijland\Marktplaats\Data\ListingTrustIndicator;
use NiekNijland\Marktplaats\Data\Location;
use NiekNijland\Marktplaats\Data\PictureAspectRatio;
use NiekNijland\Marktplaats\Data\PriceInfo;
use NiekNijland\Marktplaats\Data\SearchFacet;
use NiekNijland\Marktplaats\Data\SearchFacetAttributeGroupOption;
use NiekNijland\Marktplaats\Data\SearchFacetCategory;
use NiekNijland\Marktplaats\Data\SearchMetaTags;
use NiekNijland\Marktplaats\Data\SearchRequest;
use NiekNijland\Marktplaats\Data\SearchResult;
use NiekNijland\Marktplaats\Data\SellerInformation;
use NiekNijland\Marktplaats\Data\SortOption;
use NiekNijland\Marktplaats\Exception\ClientException;
use NiekNijland\Marktplaats\Support\UrlResolver;

class SearchParser
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function parseSearchResult(array $data): SearchResult
    {
        return new SearchResult(
            listings: $this->parseListings($data['listings'] ?? []),
            topBlock: $this->parseListings($data['topBlock'] ?? []),
            facets: $this->parseFacets($data['facets'] ?? []),
            totalResultCount: (int) ($data['totalResultCount'] ?? 0),
            maxAllowedPageNumber: (int) ($data['maxAllowedPageNumber'] ?? 0),
            correlationId: $this->toNullableString($data['correlationId'] ?? null),
            originalQuery: $this->toNullableString($data['originalQuery'] ?? null),
            sortOptions: $this->parseSortOptions($data['sortOptions'] ?? []),
            searchCategory: $this->toNullableInt($data['searchCategory'] ?? null),
            searchCategoryOptions: $this->parseSearchCategoryOptions($data['searchCategoryOptions'] ?? []),
            searchRequest: is_array($data['searchRequest'] ?? null) ? $this->parseSearchRequest($data['searchRequest']) : null,
            metaTags: is_array($data['metaTags'] ?? null) ? $this->parseMetaTags($data['metaTags']) : null,
        );
    }

    public function parseJson(string $body): SearchResult
    {
        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ClientException('Failed to decode JSON response: '.$e->getMessage(), 0, $e);
        }

        return $this->parseSearchResult($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function parseCategoryCatalog(array $data, int $parentCategoryId): CategoryCatalog
    {
        $categoryOptions = $this->normalizeListOfArrays($data['searchCategoryOptions'] ?? []);
        $categories = [];

        foreach ($categoryOptions as $option) {
            $optionParentId = isset($option['parentId']) ? (int) $option['parentId'] : null;

            if ($optionParentId !== $parentCategoryId) {
                continue;
            }

            $categories[] = new Category(
                id: (int) ($option['id'] ?? 0),
                key: isset($option['key']) ? (string) $option['key'] : null,
                name: isset($option['name']) ? (string) $option['name'] : null,
                fullName: isset($option['fullName']) ? (string) $option['fullName'] : null,
                parentId: $optionParentId,
                parentKey: isset($option['parentKey']) ? (string) $option['parentKey'] : null,
            );
        }

        return new CategoryCatalog(
            categories: $categories,
            parentCategoryId: $parentCategoryId,
            discoveredAt: new DateTimeImmutable,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return Listing[]
     */
    private function parseListings(mixed $items): array
    {
        return array_map(
            fn (array $item): Listing => $this->parseListing($item),
            $this->normalizeListOfArrays($items),
        );
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function parseListing(array $item): Listing
    {
        $vipUrl = is_string($item['vipUrl'] ?? null) ? $item['vipUrl'] : null;
        $fullUrl = null;

        if ($vipUrl !== null) {
            $fullUrl = UrlResolver::resolveAgainstBase($vipUrl);
        }

        return new Listing(
            itemId: (string) ($item['itemId'] ?? ''),
            title: (string) ($item['title'] ?? ''),
            description: $this->toNullableString($item['description'] ?? null),
            categorySpecificDescription: $this->toNullableString($item['categorySpecificDescription'] ?? null),
            categoryId: $this->toNullableInt($item['categoryId'] ?? null),
            vipUrl: $vipUrl,
            fullUrl: $fullUrl,
            priceInfo: is_array($item['priceInfo'] ?? null) ? PriceInfo::fromArray($item['priceInfo']) : null,
            location: is_array($item['location'] ?? null) ? Location::fromArray($item['location']) : null,
            imageUrls: $this->normalizeListOfStrings($item['imageUrls'] ?? []),
            pictures: array_map(
                fn (array $p): ListingPicture => $this->parsePicture($p),
                $this->normalizeListOfArrays($item['pictures'] ?? []),
            ),
            sellerInformation: is_array($item['sellerInformation'] ?? null) ? SellerInformation::fromArray($item['sellerInformation']) : null,
            attributes: array_map(
                fn (array $a): ListingAttribute => ListingAttribute::fromArray($a),
                $this->normalizeListOfArrays($item['attributes'] ?? []),
            ),
            extendedAttributes: array_map(
                fn (array $a): ListingAttribute => ListingAttribute::fromArray($a),
                $this->normalizeListOfArrays($item['extendedAttributes'] ?? []),
            ),
            traits: $this->normalizeListOfStrings($item['traits'] ?? []),
            verticals: $this->normalizeListOfStrings($item['verticals'] ?? []),
            date: $this->toNullableString($item['date'] ?? null),
            priorityProduct: $this->toNullableString($item['priorityProduct'] ?? null),
            reserved: (bool) ($item['reserved'] ?? false),
            searchType: $this->toNullableString($item['searchType'] ?? null),
            thinContent: (bool) ($item['thinContent'] ?? false),
            videoOnVip: (bool) ($item['videoOnVip'] ?? false),
            urgencyFeatureActive: (bool) ($item['urgencyFeatureActive'] ?? false),
            napAvailable: (bool) ($item['napAvailable'] ?? false),
            trackingData: $this->toNullableString($item['trackingData'] ?? null),
            pageLocation: $this->toNullableString($item['pageLocation'] ?? null),
            opvalStickerText: $this->toNullableString($item['opvalStickerText'] ?? null),
            highlights: array_map(
                fn (array $h): ListingHighlight => ListingHighlight::fromArray($h),
                $this->normalizeListOfArrays($item['highlights'] ?? []),
            ),
            trustIndicators: array_map(
                fn (array $t): ListingTrustIndicator => ListingTrustIndicator::fromArray($t),
                $this->normalizeListOfArrays($item['trustIndicators'] ?? []),
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function parsePicture(array $data): ListingPicture
    {
        $aspectRatio = null;

        if (isset($data['aspectRatio']) && is_array($data['aspectRatio'])) {
            $aspectRatio = new PictureAspectRatio(
                width: (int) ($data['aspectRatio']['width'] ?? 0),
                height: (int) ($data['aspectRatio']['height'] ?? 0),
            );
        }

        return new ListingPicture(
            id: $this->toNullableInt($data['id'] ?? null),
            mediaId: $this->toNullableString($data['mediaId'] ?? null),
            url: $this->toNullableString($data['url'] ?? null),
            extraSmallUrl: $this->toNullableString($data['extraSmallUrl'] ?? null),
            mediumUrl: $this->toNullableString($data['mediumUrl'] ?? null),
            largeUrl: $this->toNullableString($data['largeUrl'] ?? null),
            extraExtraLargeUrl: $this->toNullableString($data['extraExtraLargeUrl'] ?? null),
            sizes: $this->normalizeStringMap($data['sizes'] ?? []),
            aspectRatio: $aspectRatio,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return SearchFacet[]
     */
    private function parseFacets(mixed $items): array
    {
        return array_map(
            fn (array $item): SearchFacet => $this->parseFacet($item),
            $this->normalizeListOfArrays($items),
        );
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function parseFacet(array $item): SearchFacet
    {
        $rawType = $this->toNullableString($item['type'] ?? null);

        return new SearchFacet(
            id: $this->toNullableInt($item['id'] ?? null),
            key: $this->toNullableString($item['key'] ?? null),
            type: $rawType !== null ? SearchFacetType::tryFrom($rawType) : null,
            rawType: $rawType,
            label: $this->toNullableString($item['label'] ?? null),
            singleSelect: is_bool($item['singleSelect'] ?? null) ? $item['singleSelect'] : null,
            categoryId: $this->toNullableInt($item['categoryId'] ?? null),
            categories: array_map(
                fn (array $c): SearchFacetCategory => SearchFacetCategory::fromArray($c),
                $this->normalizeListOfArrays($item['categories'] ?? []),
            ),
            attributeGroup: array_map(
                fn (array $o): SearchFacetAttributeGroupOption => SearchFacetAttributeGroupOption::fromArray($o),
                $this->normalizeListOfArrays($item['attributeGroup'] ?? []),
            ),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return SortOption[]
     */
    private function parseSortOptions(mixed $items): array
    {
        return array_map(
            fn (array $item): SortOption => SortOption::fromArray($item),
            $this->normalizeListOfArrays($items),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return Category[]
     */
    private function parseSearchCategoryOptions(mixed $items): array
    {
        $normalized = $this->normalizeListOfArrays($items);

        return array_map(
            /** @param array<string, mixed> $item */
            fn (array $item): Category => Category::fromArray(['id' => (int) ($item['id'] ?? 0)] + $item),
            $normalized,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function parseSearchRequest(array $data): SearchRequest
    {
        return SearchRequest::fromArray($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function parseMetaTags(array $data): SearchMetaTags
    {
        return new SearchMetaTags(
            metaTitle: $this->toNullableString($data['metaTitle'] ?? null),
            metaDescription: $this->toNullableString($data['metaDescription'] ?? null),
            pageTitleH1: $this->toNullableString($data['pageTitleH1'] ?? null),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeListOfArrays(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn (mixed $item): bool => is_array($item),
        ));
    }

    /**
     * @return list<string>
     */
    private function normalizeListOfStrings(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn (mixed $item): bool => is_string($item),
        ));
    }

    /**
     * @return array<string, string>
     */
    private function normalizeStringMap(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $key => $mapValue) {
            if (! is_string($key) || ! is_string($mapValue)) {
                continue;
            }

            $result[$key] = $mapValue;
        }

        return $result;
    }

    private function toNullableInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private function toNullableString(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return null;
    }
}
