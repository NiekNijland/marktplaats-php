<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Parser;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
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
use NiekNijland\Marktplaats\Support\UrlResolver;

class ListingDetailParser
{
    public function parseHtml(string $html, string $url): ListingDetail
    {
        $configData = $this->extractConfigJson($html);
        $xpath = $this->createXPath($html);
        $description = $this->extractDescriptionFromXPath($xpath);
        $attributes = $this->extractAttributesFromXPath($xpath);

        $listing = is_array($configData['listing'] ?? null) ? $configData['listing'] : [];

        $fullUrl = UrlResolver::resolveAgainstBase($url);

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
        $gallery = is_array($data['gallery'] ?? null) ? $data['gallery'] : [];
        $images = $this->parseImages($gallery);
        $imageUrls = $this->parseImageUrls($gallery);
        $imageSizes = $this->parseImageSizes($gallery);
        $flags = is_array($data['flags'] ?? null) ? $data['flags'] : [];

        $priceInfo = is_array($data['priceInfo'] ?? null) ? PriceInfo::fromArray($data['priceInfo']) : null;
        $seller = is_array($data['seller'] ?? null) ? ListingDetailSeller::fromArray($data['seller']) : null;
        $category = is_array($data['category'] ?? null) ? ListingDetailCategory::fromArray($data['category']) : null;
        $stats = is_array($data['stats'] ?? null) ? ListingDetailStats::fromArray($data['stats']) : null;
        $bidsInfo = is_array($data['bidsInfo'] ?? null) ? ListingDetailBidsInfo::fromArray($data['bidsInfo']) : null;
        $shipping = is_array($data['shippingInformation'] ?? null) ? ListingDetailShipping::fromArray($data['shippingInformation']) : null;

        return new ListingDetail(
            itemId: (string) ($data['itemId'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            description: $description,
            adType: $this->toNullableString($data['adType'] ?? null),
            priceInfo: $priceInfo,
            seller: $seller,
            category: $category,
            stats: $stats,
            bidsInfo: $bidsInfo,
            shipping: $shipping,
            images: $images,
            imageUrls: $imageUrls,
            imageSizes: $imageSizes,
            galleryAlt: isset($gallery['alt']) ? (string) $gallery['alt'] : null,
            attributes: $attributes,
            traits: $this->normalizeListOfStrings($data['traits'] ?? []),
            buyItNowEnabled: (bool) ($data['buyItNowEnabled'] ?? false),
            buyersProtectionAllowed: (bool) ($data['buyersProtectionAllowed'] ?? false),
            thinContent: (bool) ($data['thinContent'] ?? false),
            isAutomotiveAd: (bool) ($data['isAutomotiveAd'] ?? false),
            isFreeAd: (bool) ($data['isFreeAd'] ?? false),
            shippable: (bool) ($flags['shippable'] ?? false),
            fullUrl: $fullUrl,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function extractConfigJson(string $html): array
    {
        $markerPosition = strpos($html, 'window.__CONFIG__');

        if ($markerPosition === false) {
            throw new ClientException('Could not extract __CONFIG__ JSON from listing page');
        }

        $jsonStart = strpos($html, '{', $markerPosition);

        if ($jsonStart === false) {
            throw new ClientException('Could not extract __CONFIG__ JSON from listing page');
        }

        $json = $this->extractBalancedJsonObject($html, $jsonStart);

        if ($json === null) {
            throw new ClientException('Could not extract __CONFIG__ JSON from listing page');
        }

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ClientException('Failed to decode listing __CONFIG__ JSON: '.$e->getMessage(), 0, $e);
        }

        return $data;
    }

    public function extractDescription(string $html): ?string
    {
        return $this->extractDescriptionFromXPath($this->createXPath($html));
    }

    /**
     * @return list<ListingDetailAttribute>
     */
    public function extractAttributes(string $html): array
    {
        return $this->extractAttributesFromXPath($this->createXPath($html));
    }

    private function extractDescriptionFromXPath(?DOMXPath $xpath): ?string
    {

        if (! $xpath instanceof DOMXPath) {
            return null;
        }

        $nodes = $xpath->query('//*[@data-collapsable="description"]');

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $descriptionNode = $nodes->item(0);

        if (! $descriptionNode instanceof DOMNode) {
            return null;
        }

        $description = $this->extractTextWithLineBreaks($descriptionNode);
        $description = preg_replace('/\n{3,}/', "\n\n", $description) ?? $description;
        $description = trim($description);

        return $description !== '' ? $description : null;
    }

    /**
     * @return list<ListingDetailAttribute>
     */
    private function extractAttributesFromXPath(?DOMXPath $xpath): array
    {
        if (! $xpath instanceof DOMXPath) {
            return [];
        }

        $attributes = [];

        $items = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " Attributes-item ")]');

        if ($items === false) {
            return [];
        }

        foreach ($items as $item) {
            if (! $item instanceof DOMElement) {
                continue;
            }

            $labelNodes = $xpath->query('.//div[contains(concat(" ", normalize-space(@class), " "), " Attributes-label ")]', $item);
            $valueNodes = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " Attributes-value ")]', $item);

            $labelNode = $labelNodes !== false ? $labelNodes->item(0) : null;
            $valueNode = $valueNodes !== false ? $valueNodes->item(0) : null;

            if (! $labelNode instanceof DOMNode || ! $valueNode instanceof DOMNode) {
                continue;
            }

            $label = trim(html_entity_decode($labelNode->textContent ?? '', ENT_QUOTES | ENT_HTML5));
            $value = '';

            $listItems = $xpath->query('.//li', $valueNode);

            if ($listItems !== false && $listItems->length > 0) {
                $values = [];

                foreach ($listItems as $listItem) {
                    if (! $listItem instanceof DOMNode) {
                        continue;
                    }

                    $listValue = trim(html_entity_decode($listItem->textContent ?? '', ENT_QUOTES | ENT_HTML5));

                    if ($listValue === '') {
                        continue;
                    }

                    $values[] = $listValue;
                }

                $value = implode(', ', $values);
            } else {
                $value = trim(html_entity_decode($valueNode->textContent ?? '', ENT_QUOTES | ENT_HTML5));
            }

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
        $media = is_array($gallery['media'] ?? null) ? $gallery['media'] : [];
        $rawImages = is_array($media['images'] ?? null) ? $media['images'] : [];

        return array_map(
            fn (array $image): ListingDetailImage => ListingDetailImage::fromArray($image),
            array_values(array_filter($rawImages, static fn (mixed $image): bool => is_array($image))),
        );
    }

    /**
     * @param  array<string, mixed>  $gallery
     * @return list<string>
     */
    private function parseImageUrls(array $gallery): array
    {
        $rawImageUrls = is_array($gallery['imageUrls'] ?? null) ? $gallery['imageUrls'] : [];

        $imageUrls = [];

        foreach ($rawImageUrls as $url) {
            if (! is_string($url)) {
                continue;
            }

            $imageUrls[] = UrlResolver::resolveProtocolRelative($url);
        }

        return $imageUrls;
    }

    /**
     * @param  array<string, mixed>  $gallery
     * @return array<string, string>
     */
    private function parseImageSizes(array $gallery): array
    {
        $media = is_array($gallery['media'] ?? null) ? $gallery['media'] : [];
        $rawImageSizes = $media['imageSizes'] ?? [];

        if (! is_array($rawImageSizes)) {
            return [];
        }

        $imageSizes = [];

        foreach ($rawImageSizes as $size => $rule) {
            if (! is_string($size)) {
                continue;
            }

            if (is_string($rule) || is_int($rule)) {
                $imageSizes[$size] = (string) $rule;
            }
        }

        return $imageSizes;
    }

    private function createXPath(string $html): ?DOMXPath
    {
        $dom = new DOMDocument;

        $previousUseInternalErrors = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        if (! $loaded) {
            return null;
        }

        return new DOMXPath($dom);
    }

    private function extractTextWithLineBreaks(DOMNode $node): string
    {
        $text = '';

        foreach ($node->childNodes as $childNode) {
            if ($childNode->nodeType === XML_TEXT_NODE) {
                $text .= html_entity_decode($childNode->textContent ?? '', ENT_QUOTES | ENT_HTML5);

                continue;
            }

            if ($childNode instanceof DOMElement && strtolower($childNode->tagName) === 'br') {
                $text .= "\n";

                continue;
            }

            $text .= $this->extractTextWithLineBreaks($childNode);
        }

        return $text;
    }

    private function extractBalancedJsonObject(string $html, int $jsonStart): ?string
    {
        $length = strlen($html);
        $depth = 0;
        $inString = false;
        $isEscaped = false;

        for ($index = $jsonStart; $index < $length; $index++) {
            $character = $html[$index];

            if ($inString) {
                if ($isEscaped) {
                    $isEscaped = false;

                    continue;
                }

                if ($character === '\\') {
                    $isEscaped = true;

                    continue;
                }

                if ($character === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($character === '"') {
                $inString = true;

                continue;
            }

            if ($character === '{') {
                $depth++;

                continue;
            }

            if ($character !== '}') {
                continue;
            }

            $depth--;

            if ($depth === 0) {
                return substr($html, $jsonStart, $index - $jsonStart + 1);
            }
        }

        return null;
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
