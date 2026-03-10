<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class Listing
{
    /**
     * @param  string[]  $imageUrls
     * @param  ListingPicture[]  $pictures
     * @param  ListingAttribute[]  $attributes
     * @param  ListingAttribute[]  $extendedAttributes
     * @param  string[]  $traits
     * @param  string[]  $verticals
     * @param  ListingHighlight[]  $highlights
     * @param  ListingTrustIndicator[]  $trustIndicators
     */
    public function __construct(
        public string $itemId,
        public string $title,
        public ?string $description,
        public ?string $categorySpecificDescription,
        public ?int $categoryId,
        public ?string $vipUrl,
        public ?string $fullUrl,
        public ?PriceInfo $priceInfo,
        public ?Location $location,
        public array $imageUrls,
        public array $pictures,
        public ?SellerInformation $sellerInformation,
        public array $attributes,
        public array $extendedAttributes,
        public array $traits,
        public array $verticals,
        public ?string $date,
        public ?string $priorityProduct,
        public bool $reserved,
        public ?string $searchType,
        public bool $thinContent,
        public bool $videoOnVip,
        public bool $urgencyFeatureActive,
        public bool $napAvailable,
        public ?string $trackingData,
        public ?string $pageLocation,
        public ?string $opvalStickerText,
        public array $highlights,
        public array $trustIndicators,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'itemId' => $this->itemId,
            'title' => $this->title,
            'description' => $this->description,
            'categorySpecificDescription' => $this->categorySpecificDescription,
            'categoryId' => $this->categoryId,
            'vipUrl' => $this->vipUrl,
            'fullUrl' => $this->fullUrl,
            'priceInfo' => $this->priceInfo?->toArray(),
            'location' => $this->location?->toArray(),
            'imageUrls' => $this->imageUrls,
            'pictures' => array_map(fn (ListingPicture $p): array => $p->toArray(), $this->pictures),
            'sellerInformation' => $this->sellerInformation?->toArray(),
            'attributes' => array_map(fn (ListingAttribute $a): array => $a->toArray(), $this->attributes),
            'extendedAttributes' => array_map(fn (ListingAttribute $a): array => $a->toArray(), $this->extendedAttributes),
            'traits' => $this->traits,
            'verticals' => $this->verticals,
            'date' => $this->date,
            'priorityProduct' => $this->priorityProduct,
            'reserved' => $this->reserved,
            'searchType' => $this->searchType,
            'thinContent' => $this->thinContent,
            'videoOnVip' => $this->videoOnVip,
            'urgencyFeatureActive' => $this->urgencyFeatureActive,
            'napAvailable' => $this->napAvailable,
            'trackingData' => $this->trackingData,
            'pageLocation' => $this->pageLocation,
            'opvalStickerText' => $this->opvalStickerText,
            'highlights' => array_map(fn (ListingHighlight $h): array => $h->toArray(), $this->highlights),
            'trustIndicators' => array_map(fn (ListingTrustIndicator $t): array => $t->toArray(), $this->trustIndicators),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            itemId: (string) ($data['itemId'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            description: is_string($data['description'] ?? null) ? $data['description'] : null,
            categorySpecificDescription: is_string($data['categorySpecificDescription'] ?? null) ? $data['categorySpecificDescription'] : null,
            categoryId: isset($data['categoryId']) ? (int) $data['categoryId'] : null,
            vipUrl: is_string($data['vipUrl'] ?? null) ? $data['vipUrl'] : null,
            fullUrl: is_string($data['fullUrl'] ?? null) ? $data['fullUrl'] : null,
            priceInfo: is_array($data['priceInfo'] ?? null) ? PriceInfo::fromArray($data['priceInfo']) : null,
            location: is_array($data['location'] ?? null) ? Location::fromArray($data['location']) : null,
            imageUrls: self::normalizeListOfStrings($data['imageUrls'] ?? []),
            pictures: array_map(
                static fn (array $p): ListingPicture => ListingPicture::fromArray($p),
                self::normalizeListOfArrays($data['pictures'] ?? []),
            ),
            sellerInformation: is_array($data['sellerInformation'] ?? null) ? SellerInformation::fromArray($data['sellerInformation']) : null,
            attributes: array_map(
                static fn (array $a): ListingAttribute => ListingAttribute::fromArray($a),
                self::normalizeListOfArrays($data['attributes'] ?? []),
            ),
            extendedAttributes: array_map(
                static fn (array $a): ListingAttribute => ListingAttribute::fromArray($a),
                self::normalizeListOfArrays($data['extendedAttributes'] ?? []),
            ),
            traits: self::normalizeListOfStrings($data['traits'] ?? []),
            verticals: self::normalizeListOfStrings($data['verticals'] ?? []),
            date: is_string($data['date'] ?? null) ? $data['date'] : null,
            priorityProduct: is_string($data['priorityProduct'] ?? null) ? $data['priorityProduct'] : null,
            reserved: $data['reserved'] ?? false,
            searchType: is_string($data['searchType'] ?? null) ? $data['searchType'] : null,
            thinContent: $data['thinContent'] ?? false,
            videoOnVip: $data['videoOnVip'] ?? false,
            urgencyFeatureActive: $data['urgencyFeatureActive'] ?? false,
            napAvailable: $data['napAvailable'] ?? false,
            trackingData: is_string($data['trackingData'] ?? null) ? $data['trackingData'] : null,
            pageLocation: is_string($data['pageLocation'] ?? null) ? $data['pageLocation'] : null,
            opvalStickerText: is_string($data['opvalStickerText'] ?? null) ? $data['opvalStickerText'] : null,
            highlights: array_map(
                static fn (array $h): ListingHighlight => ListingHighlight::fromArray($h),
                self::normalizeListOfArrays($data['highlights'] ?? []),
            ),
            trustIndicators: array_map(
                static fn (array $t): ListingTrustIndicator => ListingTrustIndicator::fromArray($t),
                self::normalizeListOfArrays($data['trustIndicators'] ?? []),
            ),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function normalizeListOfArrays(mixed $value): array
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
    private static function normalizeListOfStrings(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn (mixed $item): bool => is_string($item),
        ));
    }
}
