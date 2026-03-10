<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data\Enums;

enum OfferType: string
{
    case OFFERED = 'offered';
    case WANTED = 'wanted';
}
