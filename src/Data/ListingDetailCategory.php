<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class ListingDetailCategory
{
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public ?string $fullName = null,
        public ?int $parentId = null,
        public ?string $parentName = null,
    ) {}

    /**
     * @return array{id: ?int, name: ?string, fullName: ?string, parentId: ?int, parentName: ?string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'fullName' => $this->fullName,
            'parentId' => $this->parentId,
            'parentName' => $this->parentName,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            name: $data['name'] ?? null,
            fullName: $data['fullName'] ?? null,
            parentId: isset($data['parentId']) ? (int) $data['parentId'] : null,
            parentName: $data['parentName'] ?? null,
        );
    }
}
