<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data;

use NiekNijland\Marktplaats\Data\Enums\OfferType;
use NiekNijland\Marktplaats\Data\Enums\SortBy;
use NiekNijland\Marktplaats\Data\Enums\SortOrder;
use NiekNijland\Marktplaats\Data\Enums\ViewOptionKind;

class SearchQueryBuilder
{
    private ?string $query = null;

    private ?int $categoryId = null;

    private ?int $subCategoryId = null;

    private int $limit = 100;

    private int $offset = 0;

    private SortBy $sortBy = SortBy::SORT_INDEX;

    private SortOrder $sortOrder = SortOrder::DECREASING;

    private bool $searchInTitleAndDescription = true;

    private ViewOptionKind $viewOptions = ViewOptionKind::GALLERY_VIEW;

    private ?string $postalCode = null;

    private ?int $distanceMeters = null;

    private ?OfferType $offerType = null;

    /** @var list<AttributeRange> */
    private array $attributeRanges = [];

    /** @var list<int> */
    private array $attributesById = [];

    /** @var list<AttributeByKey> */
    private array $attributesByKey = [];

    public function query(?string $query): self
    {
        $this->query = $query;

        return $this;
    }

    public function categoryId(?int $categoryId): self
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    public function subCategoryId(?int $subCategoryId): self
    {
        $this->subCategoryId = $subCategoryId;

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function sortBy(SortBy $sortBy): self
    {
        $this->sortBy = $sortBy;

        return $this;
    }

    public function sortOrder(SortOrder $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function searchInTitleAndDescription(bool $searchInTitleAndDescription): self
    {
        $this->searchInTitleAndDescription = $searchInTitleAndDescription;

        return $this;
    }

    public function viewOptions(ViewOptionKind $viewOptions): self
    {
        $this->viewOptions = $viewOptions;

        return $this;
    }

    public function postalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function distanceMeters(?int $distanceMeters): self
    {
        $this->distanceMeters = $distanceMeters;

        return $this;
    }

    public function offerType(?OfferType $offerType): self
    {
        $this->offerType = $offerType;

        return $this;
    }

    /**
     * @param  list<AttributeRange>  $attributeRanges
     */
    public function attributeRanges(array $attributeRanges): self
    {
        $this->attributeRanges = $attributeRanges;

        return $this;
    }

    public function addAttributeRange(AttributeRange $attributeRange): self
    {
        $this->attributeRanges[] = $attributeRange;

        return $this;
    }

    /**
     * @param  list<int>  $attributesById
     */
    public function attributesById(array $attributesById): self
    {
        $this->attributesById = $attributesById;

        return $this;
    }

    public function addAttributeId(int $attributeId): self
    {
        $this->attributesById[] = $attributeId;

        return $this;
    }

    /**
     * @param  list<AttributeByKey>  $attributesByKey
     */
    public function attributesByKey(array $attributesByKey): self
    {
        $this->attributesByKey = $attributesByKey;

        return $this;
    }

    public function addAttributeByKey(AttributeByKey $attributeByKey): self
    {
        $this->attributesByKey[] = $attributeByKey;

        return $this;
    }

    public function build(): SearchQuery
    {
        return new SearchQuery(
            query: $this->query,
            categoryId: $this->categoryId,
            subCategoryId: $this->subCategoryId,
            limit: $this->limit,
            offset: $this->offset,
            sortBy: $this->sortBy,
            sortOrder: $this->sortOrder,
            searchInTitleAndDescription: $this->searchInTitleAndDescription,
            viewOptions: $this->viewOptions,
            postalCode: $this->postalCode,
            distanceMeters: $this->distanceMeters,
            offerType: $this->offerType,
            attributeRanges: $this->attributeRanges,
            attributesById: $this->attributesById,
            attributesByKey: $this->attributesByKey,
        );
    }
}
