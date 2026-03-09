<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class ListingPicture
{
    /**
     * @param  array<string, string>  $sizes
     */
    public function __construct(
        public ?int $id,
        public ?string $mediaId,
        public ?string $url,
        public ?string $extraSmallUrl,
        public ?string $mediumUrl,
        public ?string $largeUrl,
        public ?string $extraExtraLargeUrl,
        public array $sizes,
        public ?PictureAspectRatio $aspectRatio,
    ) {}

    /**
     * @return array{id: ?int, mediaId: ?string, url: ?string, extraSmallUrl: ?string, mediumUrl: ?string, largeUrl: ?string, extraExtraLargeUrl: ?string, sizes: array<string, string>, aspectRatio: ?array{width: int, height: int}}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'mediaId' => $this->mediaId,
            'url' => $this->url,
            'extraSmallUrl' => $this->extraSmallUrl,
            'mediumUrl' => $this->mediumUrl,
            'largeUrl' => $this->largeUrl,
            'extraExtraLargeUrl' => $this->extraExtraLargeUrl,
            'sizes' => $this->sizes,
            'aspectRatio' => $this->aspectRatio?->toArray(),
        ];
    }

    /**
     * @param  array{id?: ?int, mediaId?: ?string, url?: ?string, extraSmallUrl?: ?string, mediumUrl?: ?string, largeUrl?: ?string, extraExtraLargeUrl?: ?string, sizes?: array<string, string>, aspectRatio?: ?array{width?: int, height?: int}}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            mediaId: $data['mediaId'] ?? null,
            url: $data['url'] ?? null,
            extraSmallUrl: $data['extraSmallUrl'] ?? null,
            mediumUrl: $data['mediumUrl'] ?? null,
            largeUrl: $data['largeUrl'] ?? null,
            extraExtraLargeUrl: $data['extraExtraLargeUrl'] ?? null,
            sizes: $data['sizes'] ?? [],
            aspectRatio: isset($data['aspectRatio']) ? PictureAspectRatio::fromArray($data['aspectRatio']) : null,
        );
    }
}
