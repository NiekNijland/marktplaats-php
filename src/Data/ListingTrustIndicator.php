<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class ListingTrustIndicator
{
    public function __construct(
        public ?string $key,
        public ?string $label,
        public ?string $value,
    ) {}

    /**
     * @return array{key: ?string, label: ?string, value: ?string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'value' => $this->value,
        ];
    }

    /**
     * @param  array{key?: ?string, label?: ?string, value?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: $data['key'] ?? null,
            label: $data['label'] ?? null,
            value: $data['value'] ?? null,
        );
    }
}
