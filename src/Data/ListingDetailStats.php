<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class ListingDetailStats
{
    public function __construct(
        public int $viewCount = 0,
        public int $favoritedCount = 0,
        public ?string $since = null,
    ) {}

    /**
     * @return array{viewCount: int, favoritedCount: int, since: ?string}
     */
    public function toArray(): array
    {
        return [
            'viewCount' => $this->viewCount,
            'favoritedCount' => $this->favoritedCount,
            'since' => $this->since,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            viewCount: (int) ($data['viewCount'] ?? 0),
            favoritedCount: (int) ($data['favoritedCount'] ?? 0),
            since: $data['since'] ?? null,
        );
    }
}
