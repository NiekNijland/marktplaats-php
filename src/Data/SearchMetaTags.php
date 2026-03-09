<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class SearchMetaTags
{
    public function __construct(
        public ?string $metaTitle,
        public ?string $metaDescription,
        public ?string $pageTitleH1,
    ) {}

    /**
     * @return array{metaTitle: ?string, metaDescription: ?string, pageTitleH1: ?string}
     */
    public function toArray(): array
    {
        return [
            'metaTitle' => $this->metaTitle,
            'metaDescription' => $this->metaDescription,
            'pageTitleH1' => $this->pageTitleH1,
        ];
    }

    /**
     * @param  array{metaTitle?: ?string, metaDescription?: ?string, pageTitleH1?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            metaTitle: $data['metaTitle'] ?? null,
            metaDescription: $data['metaDescription'] ?? null,
            pageTitleH1: $data['pageTitleH1'] ?? null,
        );
    }
}
