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
            sellerId: isset($data['sellerId']) ? $data['sellerId'] : null,
            sellerName: is_string($data['sellerName'] ?? null) ? $data['sellerName'] : null,
            showSoiUrl: (bool) ($data['showSoiUrl'] ?? false),
            showWebsiteUrl: (bool) ($data['showWebsiteUrl'] ?? false),
            sellerWebsiteUrl: is_string($data['sellerWebsiteUrl'] ?? null) ? $data['sellerWebsiteUrl'] : null,
            sellerLogoUrl: is_string($data['sellerLogoUrl'] ?? null) ? $data['sellerLogoUrl'] : null,
            isVerified: (bool) ($data['isVerified'] ?? false),
        );
    }
}
