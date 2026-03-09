<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class ListingDetail
{
    /**
     * @param  list<ListingDetailImage>  $images
     * @param  list<string>  $imageUrls
     * @param  list<ListingDetailAttribute>  $attributes
     * @param  list<string>  $traits
     */
    public function __construct(
        public string $itemId,
        public string $title,
        public ?string $description = null,
        public ?string $adType = null,
        public ?PriceInfo $priceInfo = null,
        public ?ListingDetailSeller $seller = null,
        public ?ListingDetailCategory $category = null,
        public ?ListingDetailStats $stats = null,
        public ?ListingDetailBidsInfo $bidsInfo = null,
        public ?ListingDetailShipping $shipping = null,
        public array $images = [],
        public array $imageUrls = [],
        public array $attributes = [],
        public array $traits = [],
        public bool $buyItNowEnabled = false,
        public bool $buyersProtectionAllowed = false,
        public bool $thinContent = false,
        public bool $isAutomotiveAd = false,
        public string $fullUrl = '',
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
            'adType' => $this->adType,
            'priceInfo' => $this->priceInfo?->toArray(),
            'seller' => $this->seller?->toArray(),
            'category' => $this->category?->toArray(),
            'stats' => $this->stats?->toArray(),
            'bidsInfo' => $this->bidsInfo?->toArray(),
            'shipping' => $this->shipping?->toArray(),
            'images' => array_map(fn (ListingDetailImage $i): array => $i->toArray(), $this->images),
            'imageUrls' => $this->imageUrls,
            'attributes' => array_map(fn (ListingDetailAttribute $a): array => $a->toArray(), $this->attributes),
            'traits' => $this->traits,
            'buyItNowEnabled' => $this->buyItNowEnabled,
            'buyersProtectionAllowed' => $this->buyersProtectionAllowed,
            'thinContent' => $this->thinContent,
            'isAutomotiveAd' => $this->isAutomotiveAd,
            'fullUrl' => $this->fullUrl,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            itemId: $data['itemId'] ?? '',
            title: $data['title'] ?? '',
            description: $data['description'] ?? null,
            adType: $data['adType'] ?? null,
            priceInfo: isset($data['priceInfo']) ? PriceInfo::fromArray($data['priceInfo']) : null,
            seller: isset($data['seller']) ? ListingDetailSeller::fromArray($data['seller']) : null,
            category: isset($data['category']) ? ListingDetailCategory::fromArray($data['category']) : null,
            stats: isset($data['stats']) ? ListingDetailStats::fromArray($data['stats']) : null,
            bidsInfo: isset($data['bidsInfo']) ? ListingDetailBidsInfo::fromArray($data['bidsInfo']) : null,
            shipping: isset($data['shipping']) ? ListingDetailShipping::fromArray($data['shipping']) : null,
            images: array_values(array_map(
                fn (array $i): ListingDetailImage => ListingDetailImage::fromArray($i),
                $data['images'] ?? [],
            )),
            imageUrls: $data['imageUrls'] ?? [],
            attributes: array_values(array_map(
                fn (array $a): ListingDetailAttribute => ListingDetailAttribute::fromArray($a),
                $data['attributes'] ?? [],
            )),
            traits: $data['traits'] ?? [],
            buyItNowEnabled: (bool) ($data['buyItNowEnabled'] ?? false),
            buyersProtectionAllowed: (bool) ($data['buyersProtectionAllowed'] ?? false),
            thinContent: (bool) ($data['thinContent'] ?? false),
            isAutomotiveAd: (bool) ($data['isAutomotiveAd'] ?? false),
            fullUrl: $data['fullUrl'] ?? '',
        );
    }
}
