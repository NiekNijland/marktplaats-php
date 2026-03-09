<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class ListingDetailBidUser
{
    public function __construct(
        public ?int $id = null,
        public ?string $nickname = null,
    ) {}

    /**
     * @return array{id: ?int, nickname: ?string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nickname' => $this->nickname,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            nickname: $data['nickname'] ?? null,
        );
    }
}
