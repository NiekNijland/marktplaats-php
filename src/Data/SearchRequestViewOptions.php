<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class SearchRequestViewOptions
{
    public function __construct(
        public ?string $viewOptions,
    ) {}

    /**
     * @return array{viewOptions: ?string}
     */
    public function toArray(): array
    {
        return [
            'viewOptions' => $this->viewOptions,
        ];
    }

    /**
     * @param  array{viewOptions?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            viewOptions: $data['viewOptions'] ?? null,
        );
    }
}
