<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class SearchRequestPagination
{
    public function __construct(
        public ?int $offset,
        public ?int $limit,
    ) {}

    /**
     * @return array{offset: ?int, limit: ?int}
     */
    public function toArray(): array
    {
        return [
            'offset' => $this->offset,
            'limit' => $this->limit,
        ];
    }

    /**
     * @param  array{offset?: ?int, limit?: ?int}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            offset: $data['offset'] ?? null,
            limit: $data['limit'] ?? null,
        );
    }
}
