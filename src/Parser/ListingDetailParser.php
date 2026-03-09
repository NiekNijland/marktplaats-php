<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Parser;

use JsonException;
use NiekNijland\Marktplaats\Data\ListingDetail;
use NiekNijland\Marktplaats\Data\ListingDetailAttribute;
use NiekNijland\Marktplaats\Data\ListingDetailBidsInfo;
use NiekNijland\Marktplaats\Data\ListingDetailCategory;
use NiekNijland\Marktplaats\Data\ListingDetailImage;
use NiekNijland\Marktplaats\Data\ListingDetailSeller;
use NiekNijland\Marktplaats\Data\ListingDetailShipping;
use NiekNijland\Marktplaats\Data\ListingDetailStats;
use NiekNijland\Marktplaats\Data\PriceInfo;
use NiekNijland\Marktplaats\Exception\ClientException;

class ListingDetailParser
{
    private const string BASE_URL = 'https://www.marktplaats.nl';

    public function parseHtml(string $html, string $url): ListingDetail
    {
        $configData = $this->extractConfigJson($html);
        $description = $this->extractDescription($html);
        $attributes = $this->extractAttributes($html);

        $listing = $configData['listing'] ?? [];

        $fullUrl = $this->resolveFullUrl($url);

        return $this->buildListingDetail($listing, $description, $attributes, $fullUrl);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<ListingDetailAttribute>  $attributes
     */
    public function buildListingDetail(
        array $data,
        ?string $description,
        array $attributes,
        string $fullUrl,
    ): ListingDetail {
        $gallery = $data['gallery'] ?? [];
        $images = $this->parseImages($gallery);
        $imageUrls = $this->parseImageUrls($gallery);

        return new ListingDetail(
            itemId: (string) ($data['itemId'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            description: $description,
            adType: $data['adType'] ?? null,
            priceInfo: isset($data['priceInfo']) ? PriceInfo::fromArray($data['priceInfo']) : null,
            seller: isset($data['seller']) ? ListingDetailSeller::fromArray($data['seller']) : null,
            category: isset($data['category']) ? ListingDetailCategory::fromArray($data['category']) : null,
            stats: isset($data['stats']) ? ListingDetailStats::fromArray($data['stats']) : null,
            bidsInfo: isset($data['bidsInfo']) ? ListingDetailBidsInfo::fromArray($data['bidsInfo']) : null,
            shipping: isset($data['shippingInformation']) ? ListingDetailShipping::fromArray($data['shippingInformation']) : null,
            images: $images,
            imageUrls: $imageUrls,
            attributes: $attributes,
            traits: $data['traits'] ?? [],
            buyItNowEnabled: (bool) ($data['buyItNowEnabled'] ?? false),
            buyersProtectionAllowed: (bool) ($data['buyersProtectionAllowed'] ?? false),
            thinContent: (bool) ($data['thinContent'] ?? false),
            isAutomotiveAd: (bool) ($data['isAutomotiveAd'] ?? false),
            fullUrl: $fullUrl,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function extractConfigJson(string $html): array
    {
        if (preg_match('/window\.__CONFIG__\s*=\s*(\{.+?\})\s*;\s*<\/script>/s', $html, $matches) !== 1) {
            throw new ClientException('Could not extract __CONFIG__ JSON from listing page');
        }

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ClientException('Failed to decode listing __CONFIG__ JSON: '.$e->getMessage(), 0, $e);
        }

        return $data;
    }

    public function extractDescription(string $html): ?string
    {
        if (preg_match('/data-collapsable="description">(.*?)<\/div>/s', $html, $matches) !== 1) {
            return null;
        }

        $description = (string) preg_replace('/<br\s*\/?>/i', "\n", $matches[1]);
        $description = strip_tags($description);

        return trim($description) !== '' ? trim($description) : null;
    }

    /**
     * @return list<ListingDetailAttribute>
     */
    public function extractAttributes(string $html): array
    {
        $attributes = [];

        if (preg_match_all(
            '/<div class="Attributes-label">(.*?)<\/div>\s*<(?:div|ul) class="Attributes-value[^"]*">(.*?)<\/(?:div|ul)>/s',
            $html,
            $matches,
            PREG_SET_ORDER,
        ) === false) {
            return [];
        }

        foreach ($matches as $match) {
            $label = trim(strip_tags($match[1]));

            $value = $match[2];
            if (str_contains($value, '<li>')) {
                preg_match_all('/<li>(.*?)<\/li>/s', $value, $listItems);
                $value = implode(', ', array_map('strip_tags', $listItems[1]));
            } else {
                $value = strip_tags($value);
            }

            $value = trim($value);

            if ($label !== '' && $value !== '') {
                $attributes[] = new ListingDetailAttribute($label, $value);
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $gallery
     * @return list<ListingDetailImage>
     */
    private function parseImages(array $gallery): array
    {
        $media = $gallery['media'] ?? [];
        $rawImages = $media['images'] ?? [];

        return array_values(array_map(
            fn (array $image): ListingDetailImage => ListingDetailImage::fromArray($image),
            $rawImages,
        ));
    }

    /**
     * @param  array<string, mixed>  $gallery
     * @return list<string>
     */
    private function parseImageUrls(array $gallery): array
    {
        return array_values($gallery['imageUrls'] ?? []);
    }

    private function resolveFullUrl(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        if (str_starts_with($url, '/')) {
            return self::BASE_URL.$url;
        }

        return self::BASE_URL.'/'.$url;
    }
}
