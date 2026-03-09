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
            description: $data['description'] ?? null,
            categorySpecificDescription: $data['categorySpecificDescription'] ?? null,
            categoryId: $data['categoryId'] ?? null,
            vipUrl: $data['vipUrl'] ?? null,
            fullUrl: $data['fullUrl'] ?? null,
            priceInfo: isset($data['priceInfo']) ? PriceInfo::fromArray($data['priceInfo']) : null,
            location: isset($data['location']) ? Location::fromArray($data['location']) : null,
            imageUrls: $data['imageUrls'] ?? [],
            pictures: array_map(
                fn (array $p): ListingPicture => ListingPicture::fromArray($p),
                $data['pictures'] ?? [],
            ),
            sellerInformation: isset($data['sellerInformation']) ? SellerInformation::fromArray($data['sellerInformation']) : null,
            attributes: array_map(
                fn (array $a): ListingAttribute => ListingAttribute::fromArray($a),
                $data['attributes'] ?? [],
            ),
            extendedAttributes: array_map(
                fn (array $a): ListingAttribute => ListingAttribute::fromArray($a),
                $data['extendedAttributes'] ?? [],
            ),
            traits: $data['traits'] ?? [],
            verticals: $data['verticals'] ?? [],
            date: $data['date'] ?? null,
            priorityProduct: $data['priorityProduct'] ?? null,
            reserved: $data['reserved'] ?? false,
            searchType: $data['searchType'] ?? null,
            thinContent: $data['thinContent'] ?? false,
            videoOnVip: $data['videoOnVip'] ?? false,
            urgencyFeatureActive: $data['urgencyFeatureActive'] ?? false,
            napAvailable: $data['napAvailable'] ?? false,
            trackingData: $data['trackingData'] ?? null,
            pageLocation: $data['pageLocation'] ?? null,
            opvalStickerText: $data['opvalStickerText'] ?? null,
            highlights: array_map(
                fn (array $h): ListingHighlight => ListingHighlight::fromArray($h),
                $data['highlights'] ?? [],
            ),
            trustIndicators: array_map(
                fn (array $t): ListingTrustIndicator => ListingTrustIndicator::fromArray($t),
                $data['trustIndicators'] ?? [],
            ),
        );
    }
}
