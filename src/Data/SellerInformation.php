<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

readonly class SellerInformation
{
    public function __construct(
        public ?int $sellerId,
        public ?string $sellerName,
        public bool $showSoiUrl,
        public bool $showWebsiteUrl,
        public ?string $sellerWebsiteUrl,
        public ?string $sellerLogoUrl,
        public bool $isVerified,
    ) {}

    /**
     * @return array{sellerId: ?int, sellerName: ?string, showSoiUrl: bool, showWebsiteUrl: bool, sellerWebsiteUrl: ?string, sellerLogoUrl: ?string, isVerified: bool}
     */
    public function toArray(): array
    {
        return [
            'sellerId' => $this->sellerId,
            'sellerName' => $this->sellerName,
            'showSoiUrl' => $this->showSoiUrl,
            'showWebsiteUrl' => $this->showWebsiteUrl,
            'sellerWebsiteUrl' => $this->sellerWebsiteUrl,
            'sellerLogoUrl' => $this->sellerLogoUrl,
            'isVerified' => $this->isVerified,
        ];
    }

    /**
     * @param  array{sellerId?: ?int, sellerName?: ?string, showSoiUrl?: bool, showWebsiteUrl?: bool, sellerWebsiteUrl?: ?string, sellerLogoUrl?: ?string, isVerified?: bool}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sellerId: $data['sellerId'] ?? null,
            sellerName: $data['sellerName'] ?? null,
            showSoiUrl: $data['showSoiUrl'] ?? false,
            showWebsiteUrl: $data['showWebsiteUrl'] ?? false,
            sellerWebsiteUrl: $data['sellerWebsiteUrl'] ?? null,
            sellerLogoUrl: $data['sellerLogoUrl'] ?? null,
            isVerified: $data['isVerified'] ?? false,
        );
    }
}
