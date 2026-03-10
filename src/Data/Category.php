<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class Category
{
    public function __construct(
        public int $id,
        public ?string $key,
        public ?string $name,
        public ?string $fullName,
        public ?int $parentId,
        public ?string $parentKey,
    ) {}

    /**
     * @return array{id: int, key: ?string, name: ?string, fullName: ?string, parentId: ?int, parentKey: ?string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            'fullName' => $this->fullName,
            'parentId' => $this->parentId,
            'parentKey' => $this->parentKey,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            key: is_string($data['key'] ?? null) ? $data['key'] : null,
            name: is_string($data['name'] ?? null) ? $data['name'] : null,
            fullName: is_string($data['fullName'] ?? null) ? $data['fullName'] : null,
            parentId: isset($data['parentId']) ? (int) $data['parentId'] : null,
            parentKey: is_string($data['parentKey'] ?? null) ? $data['parentKey'] : null,
        );
    }
}
