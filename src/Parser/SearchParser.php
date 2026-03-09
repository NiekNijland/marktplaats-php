<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Parser;

use DateTimeImmutable;
use JsonException;
use NiekNijland\Marktplaats\Data\Listing;
use NiekNijland\Marktplaats\Data\ListingAttribute;
use NiekNijland\Marktplaats\Data\ListingHighlight;
use NiekNijland\Marktplaats\Data\ListingPicture;
use NiekNijland\Marktplaats\Data\ListingTrustIndicator;
use NiekNijland\Marktplaats\Data\Location;
use NiekNijland\Marktplaats\Data\MotorcycleBrand;
use NiekNijland\Marktplaats\Data\MotorcycleBrandCatalog;
use NiekNijland\Marktplaats\Data\PictureAspectRatio;
use NiekNijland\Marktplaats\Data\PriceInfo;
use NiekNijland\Marktplaats\Data\SearchCategoryOption;
use NiekNijland\Marktplaats\Data\SearchFacet;
use NiekNijland\Marktplaats\Data\SearchFacetAttributeGroupOption;
use NiekNijland\Marktplaats\Data\SearchFacetCategory;
use NiekNijland\Marktplaats\Data\SearchMetaTags;
use NiekNijland\Marktplaats\Data\SearchRequest;
use NiekNijland\Marktplaats\Data\SearchResult;
use NiekNijland\Marktplaats\Data\SellerInformation;
use NiekNijland\Marktplaats\Data\SortOption;
use NiekNijland\Marktplaats\Exception\ClientException;

class SearchParser
{
    private const string BASE_URL = 'https://www.marktplaats.nl';

    /**
     * Non-brand category names excluded from brand catalog discovery.
     *
     * @var string[]
     */
    private const array NON_BRAND_NAMES = [
        'Oldtimers',
        'Schademotoren',
        'Overige merken',
        'Zijspanmotoren',
        'Overige Motoren',
        'Quads en Trikes',
        'Motorkleding',
    ];

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
            correlationId: $data['correlationId'] ?? null,
            originalQuery: $data['originalQuery'] ?? null,
            sortOptions: $this->parseSortOptions($data['sortOptions'] ?? []),
            searchCategory: $data['searchCategory'] ?? null,
            searchCategoryOptions: $this->parseSearchCategoryOptions($data['searchCategoryOptions'] ?? []),
            searchRequest: isset($data['searchRequest']) ? $this->parseSearchRequest($data['searchRequest']) : null,
            metaTags: isset($data['metaTags']) ? $this->parseMetaTags($data['metaTags']) : null,
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
    public function parseMotorcycleBrandCatalog(array $data, int $sourceCategoryId = 678): MotorcycleBrandCatalog
    {
        $categoryOptions = $data['searchCategoryOptions'] ?? [];
        $brands = [];

        foreach ($categoryOptions as $option) {
            $fullName = $option['fullName'] ?? '';

            if (! str_starts_with($fullName, 'Motoren | ')) {
                continue;
            }

            $name = $option['name'] ?? '';

            if (in_array($name, self::NON_BRAND_NAMES, true)) {
                continue;
            }

            $parentId = $option['parentId'] ?? null;

            if ($parentId !== $sourceCategoryId) {
                continue;
            }

            $brands[] = new MotorcycleBrand(
                categoryId: (int) $option['id'],
                key: (string) ($option['key'] ?? ''),
                name: $name,
                fullName: $fullName,
                parentCategoryId: $parentId,
            );
        }

        return new MotorcycleBrandCatalog(
            brands: $brands,
            sourceCategoryId: $sourceCategoryId,
            discoveredAt: new DateTimeImmutable,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return Listing[]
     */
    private function parseListings(array $items): array
    {
        return array_map(fn (array $item): Listing => $this->parseListing($item), $items);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function parseListing(array $item): Listing
    {
        $vipUrl = $item['vipUrl'] ?? null;
        $fullUrl = null;

        if (is_string($vipUrl) && str_starts_with($vipUrl, '/')) {
            $fullUrl = self::BASE_URL.$vipUrl;
        }

        return new Listing(
            itemId: (string) ($item['itemId'] ?? ''),
            title: (string) ($item['title'] ?? ''),
            description: $item['description'] ?? null,
            categorySpecificDescription: $item['categorySpecificDescription'] ?? null,
            categoryId: $item['categoryId'] ?? null,
            vipUrl: $vipUrl,
            fullUrl: $fullUrl,
            priceInfo: isset($item['priceInfo']) ? PriceInfo::fromArray($item['priceInfo']) : null,
            location: isset($item['location']) ? Location::fromArray($item['location']) : null,
            imageUrls: $item['imageUrls'] ?? [],
            pictures: array_map(
                fn (array $p): ListingPicture => $this->parsePicture($p),
                $item['pictures'] ?? [],
            ),
            sellerInformation: isset($item['sellerInformation']) ? SellerInformation::fromArray($item['sellerInformation']) : null,
            attributes: array_map(
                fn (array $a): ListingAttribute => ListingAttribute::fromArray($a),
                $item['attributes'] ?? [],
            ),
            extendedAttributes: array_map(
                fn (array $a): ListingAttribute => ListingAttribute::fromArray($a),
                $item['extendedAttributes'] ?? [],
            ),
            traits: $item['traits'] ?? [],
            verticals: $item['verticals'] ?? [],
            date: $item['date'] ?? null,
            priorityProduct: $item['priorityProduct'] ?? null,
            reserved: (bool) ($item['reserved'] ?? false),
            searchType: $item['searchType'] ?? null,
            thinContent: (bool) ($item['thinContent'] ?? false),
            videoOnVip: (bool) ($item['videoOnVip'] ?? false),
            urgencyFeatureActive: (bool) ($item['urgencyFeatureActive'] ?? false),
            napAvailable: (bool) ($item['napAvailable'] ?? false),
            trackingData: $item['trackingData'] ?? null,
            pageLocation: $item['pageLocation'] ?? null,
            opvalStickerText: $item['opvalStickerText'] ?? null,
            highlights: array_map(
                fn (array $h): ListingHighlight => ListingHighlight::fromArray($h),
                $item['highlights'] ?? [],
            ),
            trustIndicators: array_map(
                fn (array $t): ListingTrustIndicator => ListingTrustIndicator::fromArray($t),
                $item['trustIndicators'] ?? [],
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
                width: $data['aspectRatio']['width'] ?? null,
                height: $data['aspectRatio']['height'] ?? null,
            );
        }

        return new ListingPicture(
            id: $data['id'] ?? null,
            mediaId: $data['mediaId'] ?? null,
            url: $data['url'] ?? null,
            extraSmallUrl: $data['extraSmallUrl'] ?? null,
            mediumUrl: $data['mediumUrl'] ?? null,
            largeUrl: $data['largeUrl'] ?? null,
            extraExtraLargeUrl: $data['extraExtraLargeUrl'] ?? null,
            sizes: $data['sizes'] ?? [],
            aspectRatio: $aspectRatio,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return SearchFacet[]
     */
    private function parseFacets(array $items): array
    {
        return array_map(fn (array $item): SearchFacet => $this->parseFacet($item), $items);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function parseFacet(array $item): SearchFacet
    {
        return new SearchFacet(
            id: $item['id'] ?? null,
            key: $item['key'] ?? null,
            type: $item['type'] ?? null,
            label: $item['label'] ?? null,
            singleSelect: $item['singleSelect'] ?? null,
            categoryId: $item['categoryId'] ?? null,
            categories: array_map(
                fn (array $c): SearchFacetCategory => SearchFacetCategory::fromArray($c),
                $item['categories'] ?? [],
            ),
            attributeGroup: array_map(
                fn (array $o): SearchFacetAttributeGroupOption => SearchFacetAttributeGroupOption::fromArray($o),
                $item['attributeGroup'] ?? [],
            ),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return SortOption[]
     */
    private function parseSortOptions(array $items): array
    {
        return array_map(fn (array $item): SortOption => SortOption::fromArray($item), $items);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return SearchCategoryOption[]
     */
    private function parseSearchCategoryOptions(array $items): array
    {
        return array_map(
            /** @param array<string, mixed> $item */
            fn (array $item): SearchCategoryOption => SearchCategoryOption::fromArray(['id' => (int) ($item['id'] ?? 0)] + $item),
            $items,
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
            metaTitle: $data['metaTitle'] ?? null,
            metaDescription: $data['metaDescription'] ?? null,
            pageTitleH1: $data['pageTitleH1'] ?? null,
        );
    }
}
