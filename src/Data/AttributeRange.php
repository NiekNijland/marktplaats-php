<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class AttributeRange
{
    public function __construct(
        public string $attribute,
        public ?int $from = null,
        public ?int $to = null,
    ) {}

    public function toString(): string
    {
        return $this->attribute.':'.($this->from ?? '').':'.($this->to ?? '');
    }

    /**
     * @return array{attribute: string, from: ?int, to: ?int}
     */
    public function toArray(): array
    {
        return [
            'attribute' => $this->attribute,
            'from' => $this->from,
            'to' => $this->to,
        ];
    }

    /**
     * @param  array{attribute?: string, from?: ?int, to?: ?int}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            attribute: $data['attribute'] ?? '',
            from: $data['from'] ?? null,
            to: $data['to'] ?? null,
        );
    }
}
