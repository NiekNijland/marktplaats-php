<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class ListingDetailImage
{
    private const string BASE_URL = 'https://www.marktplaats.nl';

    public function __construct(
        public string $mediaId,
        public ?string $baseUrl = null,
        public ?int $originalWidth = null,
        public ?int $originalHeight = null,
        public ?PictureAspectRatio $aspectRatio = null,
    ) {}

    /**
     * @param  array<string, string>  $imageSizes
     */
    public function getUrlForSize(string $size, array $imageSizes): ?string
    {
        $rule = $imageSizes[$size] ?? null;

        if (! is_string($rule) || $rule === '') {
            return null;
        }

        return $this->getUrlForRule($rule);
    }

    public function getUrlForRule(string $rule): ?string
    {
        $baseUrl = $this->getResolvedBaseUrl();

        if ($baseUrl === null) {
            return null;
        }

        $baseWithoutQuery = explode('?', $baseUrl, 2)[0];

        return $baseWithoutQuery.'?rule=$_'.$rule.'.jpg';
    }

    public function getResolvedBaseUrl(): ?string
    {
        if ($this->baseUrl === null || $this->baseUrl === '') {
            return null;
        }

        if (str_starts_with($this->baseUrl, 'http://') || str_starts_with($this->baseUrl, 'https://')) {
            return $this->baseUrl;
        }

        if (str_starts_with($this->baseUrl, '//')) {
            return 'https:'.$this->baseUrl;
        }

        if (str_starts_with($this->baseUrl, '/')) {
            return self::BASE_URL.$this->baseUrl;
        }

        return self::BASE_URL.'/'.$this->baseUrl;
    }

    /**
     * @return array{mediaId: string, baseUrl: ?string, originalWidth: ?int, originalHeight: ?int, aspectRatio: ?array{width: int, height: int}}
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
            mediaId: $data['mediaId'] ?? '',
            baseUrl: $data['base'] ?? $data['baseUrl'] ?? null,
            originalWidth: isset($data['originalWidth']) ? (int) $data['originalWidth'] : null,
            originalHeight: isset($data['originalHeight']) ? (int) $data['originalHeight'] : null,
            aspectRatio: isset($data['aspectRatio']) ? PictureAspectRatio::fromArray($data['aspectRatio']) : null,
        );
    }
}
