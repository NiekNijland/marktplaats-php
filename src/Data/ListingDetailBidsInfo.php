<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class ListingDetailBidsInfo
{
    /**
     * @param  list<ListingDetailBid>  $bids
     */
    public function __construct(
        public bool $isBiddingEnabled = false,
        public ?int $currentMinimumBidCents = null,
        public array $bids = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'isBiddingEnabled' => $this->isBiddingEnabled,
            'currentMinimumBidCents' => $this->currentMinimumBidCents,
            'bids' => array_map(fn (ListingDetailBid $bid): array => $bid->toArray(), $this->bids),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            isBiddingEnabled: (bool) ($data['isBiddingEnabled'] ?? false),
            currentMinimumBidCents: isset($data['currentMinimumBid']) ? (int) $data['currentMinimumBid'] : null,
            bids: array_values(array_map(
                fn (array $bid): ListingDetailBid => ListingDetailBid::fromArray($bid),
                $data['bids'] ?? [],
            )),
        );
    }
}
