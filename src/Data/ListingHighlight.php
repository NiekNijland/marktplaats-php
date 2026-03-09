<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class ListingHighlight
{
    public function __construct(
        public string $key,
        public ?string $value,
    ) {}

    /**
     * @return array{key: string, value: ?string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
        ];
    }

    /**
     * @param  array{key?: string, value?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: $data['key'] ?? '',
            value: $data['value'] ?? null,
        );
    }
}
