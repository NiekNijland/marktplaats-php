<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

use NiekNijland\Marktplaats\Data\Enums\ListingAdType;

readonly class ListingDetail
{
    /**
     * @param  list<ListingDetailImage>  $images
     * @param  list<string>  $imageUrls
     * @param  array<string, string>  $imageSizes
     * @param  list<ListingDetailAttribute>  $attributes
     * @param  list<string>  $traits
     */
    public function __construct(
        public string $itemId,
        public string $title,
        public ?string $description = null,
        public ?ListingAdType $adType = null,
        public ?string $rawAdType = null,
        public ?PriceInfo $priceInfo = null,
        public ?ListingDetailSeller $seller = null,
        public ?ListingDetailCategory $category = null,
        public ?ListingDetailStats $stats = null,
        public ?ListingDetailBidsInfo $bidsInfo = null,
        public ?ListingDetailShipping $shipping = null,
        public array $images = [],
        public array $imageUrls = [],
        public array $imageSizes = [],
        public ?string $galleryAlt = null,
        public array $attributes = [],
        public array $traits = [],
        public bool $buyItNowEnabled = false,
        public bool $buyersProtectionAllowed = false,
        public bool $thinContent = false,
        public bool $isAutomotiveAd = false,
        public bool $isFreeAd = false,
        public bool $shippable = false,
        public string $fullUrl = '',
    ) {}

    public function getImageUrl(int $index, string $size = 'XL'): ?string
    {
        $image = $this->images[$index] ?? null;

        if (! $image instanceof ListingDetailImage) {
            return null;
        }

        return $image->getUrlForSize($size, $this->imageSizes);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'itemId' => $this->itemId,
            'title' => $this->title,
            'description' => $this->description,
            'adType' => $this->rawAdType ?? $this->adType?->value,
            'priceInfo' => $this->priceInfo?->toArray(),
            'seller' => $this->seller?->toArray(),
            'category' => $this->category?->toArray(),
            'stats' => $this->stats?->toArray(),
            'bidsInfo' => $this->bidsInfo?->toArray(),
            'shipping' => $this->shipping?->toArray(),
            'images' => array_map(static fn (ListingDetailImage $i): array => $i->toArray(), $this->images),
            'imageUrls' => $this->imageUrls,
            'imageSizes' => $this->imageSizes,
            'galleryAlt' => $this->galleryAlt,
            'attributes' => array_map(static fn (ListingDetailAttribute $a): array => $a->toArray(), $this->attributes),
            'traits' => $this->traits,
            'buyItNowEnabled' => $this->buyItNowEnabled,
            'buyersProtectionAllowed' => $this->buyersProtectionAllowed,
            'thinContent' => $this->thinContent,
            'isAutomotiveAd' => $this->isAutomotiveAd,
            'isFreeAd' => $this->isFreeAd,
            'shippable' => $this->shippable,
            'fullUrl' => $this->fullUrl,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $rawAdType = is_string($data['adType'] ?? null) ? $data['adType'] : null;

        $imageSizes = [];

        if (is_array($data['imageSizes'] ?? null)) {
            foreach ($data['imageSizes'] as $size => $rule) {
                if (! is_string($size)) {
                    continue;
                }

                if (is_string($rule) || is_int($rule)) {
                    $imageSizes[$size] = (string) $rule;
                }
            }
        }

        return new self(
            itemId: $data['itemId'] ?? '',
            title: $data['title'] ?? '',
            description: $data['description'] ?? null,
            adType: $rawAdType !== null ? ListingAdType::tryFrom($rawAdType) : null,
            rawAdType: $rawAdType,
            priceInfo: is_array($data['priceInfo'] ?? null) ? PriceInfo::fromArray($data['priceInfo']) : null,
            seller: is_array($data['seller'] ?? null) ? ListingDetailSeller::fromArray($data['seller']) : null,
            category: is_array($data['category'] ?? null) ? ListingDetailCategory::fromArray($data['category']) : null,
            stats: is_array($data['stats'] ?? null) ? ListingDetailStats::fromArray($data['stats']) : null,
            bidsInfo: is_array($data['bidsInfo'] ?? null) ? ListingDetailBidsInfo::fromArray($data['bidsInfo']) : null,
            shipping: is_array($data['shipping'] ?? null) ? ListingDetailShipping::fromArray($data['shipping']) : null,
            images: array_map(
                static fn (array $i): ListingDetailImage => ListingDetailImage::fromArray($i),
                self::normalizeListOfArrays($data['images'] ?? []),
            ),
            imageUrls: self::normalizeListOfStrings($data['imageUrls'] ?? []),
            imageSizes: $imageSizes,
            galleryAlt: is_string($data['galleryAlt'] ?? null) ? $data['galleryAlt'] : null,
            attributes: array_map(
                static fn (array $a): ListingDetailAttribute => ListingDetailAttribute::fromArray($a),
                self::normalizeListOfArrays($data['attributes'] ?? []),
            ),
            traits: self::normalizeListOfStrings($data['traits'] ?? []),
            buyItNowEnabled: (bool) ($data['buyItNowEnabled'] ?? false),
            buyersProtectionAllowed: (bool) ($data['buyersProtectionAllowed'] ?? false),
            thinContent: (bool) ($data['thinContent'] ?? false),
            isAutomotiveAd: (bool) ($data['isAutomotiveAd'] ?? false),
            isFreeAd: (bool) ($data['isFreeAd'] ?? false),
            shippable: (bool) ($data['shippable'] ?? false),
            fullUrl: $data['fullUrl'] ?? '',
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
