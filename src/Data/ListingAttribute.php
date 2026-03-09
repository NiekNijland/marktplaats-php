<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class ListingAttribute
{
    /**
     * @param  string[]  $values
     */
    public function __construct(
        public ?string $key,
        public ?string $value,
        public array $values,
    ) {}

    /**
     * @return array{key: ?string, value: ?string, values: string[]}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
            'values' => $this->values,
        ];
    }

    /**
     * @param  array{key?: ?string, value?: ?string, values?: string[]}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: $data['key'] ?? null,
            value: $data['value'] ?? null,
            values: $data['values'] ?? [],
        );
    }
}
