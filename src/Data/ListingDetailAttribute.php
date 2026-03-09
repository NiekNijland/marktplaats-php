<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class ListingDetailAttribute
{
    public function __construct(
        public string $label,
        public string $value,
    ) {}

    /**
     * @return array{label: string, value: string}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'value' => $this->value,
        ];
    }

    /**
     * @param  array{label?: string, value?: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            label: $data['label'] ?? '',
            value: $data['value'] ?? '',
        );
    }
}
