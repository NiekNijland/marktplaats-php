<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class PictureAspectRatio
{
    public function __construct(
        public ?int $width,
        public ?int $height,
    ) {}

    /**
     * @return array{width: ?int, height: ?int}
     */
    public function toArray(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    /**
     * @param  array{width?: ?int, height?: ?int}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            width: $data['width'] ?? null,
            height: $data['height'] ?? null,
        );
    }
}
