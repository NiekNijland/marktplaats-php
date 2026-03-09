<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class SearchRequestCategories
{
    public function __construct(
        public ?int $l1CategoryId,
        public ?string $l1CategoryKey,
        public ?string $l1CategoryFullName,
        public ?int $l2CategoryId,
        public ?string $l2CategoryKey,
        public ?string $l2CategoryFullName,
    ) {}

    /**
     * @return array{l1CategoryId: ?int, l1CategoryKey: ?string, l1CategoryFullName: ?string, l2CategoryId: ?int, l2CategoryKey: ?string, l2CategoryFullName: ?string}
     */
    public function toArray(): array
    {
        return [
            'l1CategoryId' => $this->l1CategoryId,
            'l1CategoryKey' => $this->l1CategoryKey,
            'l1CategoryFullName' => $this->l1CategoryFullName,
            'l2CategoryId' => $this->l2CategoryId,
            'l2CategoryKey' => $this->l2CategoryKey,
            'l2CategoryFullName' => $this->l2CategoryFullName,
        ];
    }

    /**
     * @param  array{l1CategoryId?: ?int, l1CategoryKey?: ?string, l1CategoryFullName?: ?string, l2CategoryId?: ?int, l2CategoryKey?: ?string, l2CategoryFullName?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            l1CategoryId: $data['l1CategoryId'] ?? null,
            l1CategoryKey: $data['l1CategoryKey'] ?? null,
            l1CategoryFullName: $data['l1CategoryFullName'] ?? null,
            l2CategoryId: $data['l2CategoryId'] ?? null,
            l2CategoryKey: $data['l2CategoryKey'] ?? null,
            l2CategoryFullName: $data['l2CategoryFullName'] ?? null,
        );
    }
}
