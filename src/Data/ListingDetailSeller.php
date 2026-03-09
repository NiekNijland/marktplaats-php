<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class ListingDetailSeller
{
    /**
     * @param  list<string>  $contactOptions
     */
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public ?string $pageUrl = null,
        public ?string $sellerType = null,
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
            'sellerType' => $this->sellerType,
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
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            name: $data['name'] ?? null,
            pageUrl: $data['pageUrl'] ?? null,
            sellerType: $data['sellerType'] ?? null,
            activeYears: isset($data['activeYears']) ? (int) $data['activeYears'] : null,
            isAsqEnabled: (bool) ($data['isAsqEnabled'] ?? false),
            showSellerReviews: (bool) ($data['showSellerReviews'] ?? false),
            showVerifications: (bool) ($data['showVerifications'] ?? false),
            financeAvailable: (bool) ($data['financeAvailable'] ?? false),
            location: isset($data['location']) ? ListingDetailLocation::fromArray($data['location']) : null,
            contactOptions: $data['contactOptions'] ?? [],
        );
    }
}
