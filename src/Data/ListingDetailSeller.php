<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

use NiekNijland\Marktplaats\Data\Enums\SellerType;

readonly class ListingDetailSeller
{
    /**
     * @param  list<string>  $contactOptions
     */
    public function __construct(
        public int $id,
        public ?string $name = null,
        public ?string $pageUrl = null,
        public ?SellerType $sellerType = null,
        public ?string $rawSellerType = null,
        public ?int $activeYears = null,
        public bool $isAsqEnabled = false,
        public bool $showSellerReviews = false,
        public bool $showVerifications = false,
        public bool $financeAvailable = false,
        public ?ListingDetailLocation $location = null,
        public array $contactOptions = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'pageUrl' => $this->pageUrl,
            'sellerType' => $this->rawSellerType ?? $this->sellerType?->value,
            'activeYears' => $this->activeYears,
            'isAsqEnabled' => $this->isAsqEnabled,
            'showSellerReviews' => $this->showSellerReviews,
            'showVerifications' => $this->showVerifications,
            'financeAvailable' => $this->financeAvailable,
            'location' => $this->location?->toArray(),
            'contactOptions' => $this->contactOptions,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $rawSellerType = is_string($data['sellerType'] ?? null) ? $data['sellerType'] : null;

        return new self(
            id: (int) ($data['id'] ?? 0),
            name: is_string($data['name'] ?? null) ? $data['name'] : null,
            pageUrl: is_string($data['pageUrl'] ?? null) ? $data['pageUrl'] : null,
            sellerType: $rawSellerType !== null ? SellerType::tryFrom($rawSellerType) : null,
            rawSellerType: $rawSellerType,
            activeYears: isset($data['activeYears']) ? (int) $data['activeYears'] : null,
            isAsqEnabled: (bool) ($data['isAsqEnabled'] ?? false),
            showSellerReviews: (bool) ($data['showSellerReviews'] ?? false),
            showVerifications: (bool) ($data['showVerifications'] ?? false),
            financeAvailable: (bool) ($data['financeAvailable'] ?? false),
            location: is_array($data['location'] ?? null) ? ListingDetailLocation::fromArray($data['location']) : null,
            contactOptions: self::normalizeListOfStrings($data['contactOptions'] ?? []),
        );
    }

    /**
     * @return list<string>
     */
    private static function normalizeListOfStrings(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn (mixed $item): bool => is_string($item),
        ));
    }
}
