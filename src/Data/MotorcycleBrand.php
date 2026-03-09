<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class MotorcycleBrand
{
    public function __construct(
        public int $categoryId,
        public string $key,
        public string $name,
        public string $fullName,
        public int $parentCategoryId,
    ) {}

    /**
     * @return array{categoryId: int, key: string, name: string, fullName: string, parentCategoryId: int}
     */
    public function toArray(): array
    {
        return [
            'categoryId' => $this->categoryId,
            'key' => $this->key,
            'name' => $this->name,
            'fullName' => $this->fullName,
            'parentCategoryId' => $this->parentCategoryId,
        ];
    }

    /**
     * @param  array{categoryId: int, key: string, name: string, fullName: string, parentCategoryId: int}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            categoryId: $data['categoryId'],
            key: $data['key'],
            name: $data['name'],
            fullName: $data['fullName'],
            parentCategoryId: $data['parentCategoryId'],
        );
    }
}
