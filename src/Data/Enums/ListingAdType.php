<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data\Enums;

enum ListingAdType: string
{
    case REGULAR_FREE = 'RegularFree';
    case REGULAR_PAID = 'RegularPaid';
    case TOP_AD = 'TopAd';
}
