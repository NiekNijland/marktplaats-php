<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class ListingDetailImage
{
    public function __construct(
        public ?string $mediaId = null,
        public ?string $baseUrl = null,
        public ?int $originalWidth = null,
        public ?int $originalHeight = null,
        public ?PictureAspectRatio $aspectRatio = null,
    ) {}

    /**
     * @return array{mediaId: ?string, baseUrl: ?string, originalWidth: ?int, originalHeight: ?int, aspectRatio: ?array{width: ?int, height: ?int}}
     */
    public function toArray(): array
    {
        return [
            'mediaId' => $this->mediaId,
            'baseUrl' => $this->baseUrl,
            'originalWidth' => $this->originalWidth,
            'originalHeight' => $this->originalHeight,
            'aspectRatio' => $this->aspectRatio?->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            mediaId: $data['mediaId'] ?? null,
            baseUrl: $data['base'] ?? $data['baseUrl'] ?? null,
            originalWidth: isset($data['originalWidth']) ? (int) $data['originalWidth'] : null,
            originalHeight: isset($data['originalHeight']) ? (int) $data['originalHeight'] : null,
            aspectRatio: isset($data['aspectRatio']) ? PictureAspectRatio::fromArray($data['aspectRatio']) : null,
        );
    }
}
