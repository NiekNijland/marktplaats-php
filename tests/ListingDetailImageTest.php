<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Tests;

use NiekNijland\Marktplaats\Data\ListingDetailImage;
use PHPUnit\Framework\TestCase;

class ListingDetailImageTest extends TestCase
{
    public function test_get_url_for_rule_replaces_template_placeholder(): void
    {
        $image = new ListingDetailImage(
            mediaId: 'abc',
            baseUrl: '//images.marktplaats.com/api/v1/listing-mp-p/images/22/221432d0.jpg?rule=ecg_mp_eps$_#.jpg',
        );

        $this->assertSame(
            'https://images.marktplaats.com/api/v1/listing-mp-p/images/22/221432d0.jpg?rule=ecg_mp_eps$_84.jpg',
            $image->getUrlForRule('84'),
        );
    }

    public function test_get_url_for_rule_replaces_existing_rule_value(): void
    {
        $image = new ListingDetailImage(
            mediaId: 'abc',
            baseUrl: '//images.marktplaats.com/api/v1/listing-mp-p/images/22/221432d0.jpg?rule=ecg_mp_eps$_82.jpg',
        );

        $this->assertSame(
            'https://images.marktplaats.com/api/v1/listing-mp-p/images/22/221432d0.jpg?rule=ecg_mp_eps$_84.jpg',
            $image->getUrlForRule('84'),
        );
    }
}
