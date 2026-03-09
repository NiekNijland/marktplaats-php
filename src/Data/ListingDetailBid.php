<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class ListingDetailBid
{
    public function __construct(
        public ?int $id = null,
        public ?int $valueCents = null,
        public ?string $date = null,
        public ?ListingDetailBidUser $user = null,
    ) {}

    /**
     * @return array{id: ?int, valueCents: ?int, date: ?string, user: ?array{id: ?int, nickname: ?string}}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'valueCents' => $this->valueCents,
            'date' => $this->date,
            'user' => $this->user?->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            valueCents: isset($data['value']) ? (int) $data['value'] : null,
            date: $data['date'] ?? null,
            user: isset($data['user']) ? ListingDetailBidUser::fromArray($data['user']) : null,
        );
    }
}
