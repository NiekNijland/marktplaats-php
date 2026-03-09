<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class SearchCategoryOption
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
     * @param  array{id: int, key?: ?string, name?: ?string, fullName?: ?string, parentId?: ?int, parentKey?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            key: $data['key'] ?? null,
            name: $data['name'] ?? null,
            fullName: $data['fullName'] ?? null,
            parentId: $data['parentId'] ?? null,
            parentKey: is_string($data['parentKey'] ?? null) ? $data['parentKey'] : null,
        );
    }
}
