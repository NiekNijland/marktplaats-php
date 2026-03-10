<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Support;

class UrlResolver
{
    private const string BASE_URL = 'https://www.marktplaats.nl';

    public static function resolveAgainstBase(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        if (str_starts_with($url, '/')) {
            return self::BASE_URL.$url;
        }

        return self::BASE_URL.'/'.$url;
    }

    public static function resolveProtocolRelative(string $url): string
    {
        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        return $url;
    }
}
