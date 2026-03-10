<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Data\Enums;

enum SellerType: string
{
    case CONSUMER = 'CONSUMER';
    case PRIVATE = 'PRIVATE';
    case TRADER = 'TRADER';
}
