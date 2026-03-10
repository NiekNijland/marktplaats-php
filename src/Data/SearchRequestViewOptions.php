<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

use NiekNijland\Marktplaats\Data\Enums\ViewOptionKind;

readonly class SearchRequestViewOptions
{
    public function __construct(
        public ?ViewOptionKind $viewOptions,
        public ?string $rawViewOptions = null,
    ) {}

    /**
     * @return array{viewOptions: ?string}
     */
    public function toArray(): array
    {
        return [
            'viewOptions' => $this->rawViewOptions ?? $this->viewOptions?->value,
        ];
    }

    /**
     * @param  array{viewOptions?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        $rawViewOptions = is_string($data['viewOptions'] ?? null) ? $data['viewOptions'] : null;

        return new self(
            viewOptions: $rawViewOptions !== null ? ViewOptionKind::tryFrom($rawViewOptions) : null,
            rawViewOptions: $rawViewOptions,
        );
    }
}
