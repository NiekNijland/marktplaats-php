<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data\Enums;

enum PriceType: string
{
    case FAST_BID = 'FAST_BID';
    case FIXED = 'FIXED';
    case MIN_BID = 'MIN_BID';
    case NOTK = 'NOTK';
    case ON_REQUEST = 'ON_REQUEST';
    case RESERVED = 'RESERVED';
    case SEE_DESCRIPTION = 'SEE_DESCRIPTION';
}
