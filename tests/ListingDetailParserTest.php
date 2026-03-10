<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Tests;

use NiekNijland\Marktplaats\Data\Enums\PriceType;
use NiekNijland\Marktplaats\Data\ListingDetail;
use NiekNijland\Marktplaats\Data\ListingDetailAttribute;
use NiekNijland\Marktplaats\Exception\ClientException;
use NiekNijland\Marktplaats\Parser\ListingDetailParser;
use PHPUnit\Framework\TestCase;

class ListingDetailParserTest extends TestCase
{
    private ListingDetailParser $parser;

    private string $fixtureHtml;

    protected function setUp(): void
    {
        $this->parser = new ListingDetailParser;

        $fixturePath = __DIR__.'/Fixtures/listing-detail.html';
        $this->assertFileExists($fixturePath, 'Listing detail fixture file must exist');
        $this->fixtureHtml = file_get_contents($fixturePath);
    }

    public function test_parse_html_returns_listing_detail(): void
    {
        $detail = $this->parser->parseHtml(
            $this->fixtureHtml,
            '/v/motoren/motoren-yamaha/m2355451324-yamaha-mt-07-abs-handvatverwarming',
        );

        $this->assertInstanceOf(ListingDetail::class, $detail);
        $this->assertSame('m2355451324', $detail->itemId);
        $this->assertSame('Yamaha MT-07 ABS + handvatverwarming', $detail->title);
    }

    public function test_parse_html_extracts_description(): void
    {
        $detail = $this->parser->parseHtml($this->fixtureHtml, '/v/test');

        $this->assertNotNull($detail->description);
        $this->assertStringContainsString('betrouwbare naked bike', $detail->description);
        $this->assertStringContainsString('689cc tweecilinder motor', $detail->description);
    }

    public function test_parse_html_extracts_price_info(): void
    {
        $detail = $this->parser->parseHtml($this->fixtureHtml, '/v/test');

        $this->assertNotNull($detail->priceInfo);
        $this->assertSame(630000, $detail->priceInfo->priceCents);
        $this->assertSame(PriceType::MIN_BID, $detail->priceInfo->priceType);
    }

    public function test_parse_html_extracts_seller(): void
    {
        $detail = $this->parser->parseHtml($this->fixtureHtml, '/v/test');

        $this->assertNotNull($detail->seller);
        $this->assertSame(48042674, $detail->seller->id);
        $this->assertSame('Maris45', $detail->seller->name);
        $this->assertSame('CONSUMER', $detail->seller->sellerType);
        $this->assertSame(3, $detail->seller->activeYears);
        $this->assertTrue($detail->seller->isAsqEnabled);
        $this->assertSame(['asq'], $detail->seller->contactOptions);
    }

    public function test_parse_html_extracts_seller_location(): void
    {
        $detail = $this->parser->parseHtml($this->fixtureHtml, '/v/test');

        $this->assertNotNull($detail->seller?->location);
        $this->assertSame('Hoogezand', $detail->seller->location->cityName);
        $this->assertSame('Nederland', $detail->seller->location->countryName);
        $this->assertSame('NL', $detail->seller->location->countryAbbreviation);
        $this->assertFalse($detail->seller->location->isAbroad);
        $this->assertEqualsWithDelta(53.1555, $detail->seller->location->latitude, 0.001);
        $this->assertEqualsWithDelta(6.7774, $detail->seller->location->longitude, 0.001);
    }

    public function test_parse_html_extracts_category(): void
    {
        $detail = $this->parser->parseHtml($this->fixtureHtml, '/v/test');

        $this->assertNotNull($detail->category);
        $this->assertSame(710, $detail->category->id);
        $this->assertSame('Yamaha', $detail->category->name);
        $this->assertSame('Motoren | Yamaha', $detail->category->fullName);
        $this->assertSame(678, $detail->category->parentId);
        $this->assertSame('Motoren', $detail->category->parentName);
    }

    public function test_parse_html_extracts_stats(): void
    {
        $detail = $this->parser->parseHtml($this->fixtureHtml, '/v/test');

        $this->assertNotNull($detail->stats);
        $this->assertSame(3195, $detail->stats->viewCount);
        $this->assertSame(76, $detail->stats->favoritedCount);
        $this->assertSame('2026-01-14T12:38:02Z', $detail->stats->since);
    }

    public function test_parse_html_extracts_bids_info(): void
    {
        $detail = $this->parser->parseHtml($this->fixtureHtml, '/v/test');

        $this->assertNotNull($detail->bidsInfo);
        $this->assertTrue($detail->bidsInfo->isBiddingEnabled);
        $this->assertFalse($detail->bidsInfo->isRemovingBidEnabled);
        $this->assertSame(500000, $detail->bidsInfo->currentMinimumBidCents);
        $this->assertCount(2, $detail->bidsInfo->bids);

        $firstBid = $detail->bidsInfo->bids[0];
        $this->assertSame(590000, $firstBid->valueCents);
        $this->assertSame('2026-02-27T18:48:38Z', $firstBid->date);
        $this->assertNotNull($firstBid->user);
        $this->assertSame(1901877, $firstBid->user->id);
        $this->assertSame('Harm Slooff', $firstBid->user->nickname);
    }

    public function test_parse_html_extracts_images(): void
    {
        $detail = $this->parser->parseHtml($this->fixtureHtml, '/v/test');

        $this->assertCount(2, $detail->images);
        $this->assertSame('9c4f50d7-d72a-4fb4-82a8-2916e40b8de7', $detail->images[0]->mediaId);
        $this->assertStringStartsWith('https://', $detail->images[0]->getResolvedBaseUrl() ?? '');
        $this->assertSame(828, $detail->images[0]->originalWidth);
        $this->assertSame(615, $detail->images[0]->originalHeight);
        $this->assertNotNull($detail->images[0]->aspectRatio);
        $this->assertSame(276, $detail->images[0]->aspectRatio->width);

        $this->assertCount(2, $detail->imageUrls);
        $this->assertStringStartsWith('https://', $detail->imageUrls[0]);
        $this->assertStringContainsString('221432d0', $detail->imageUrls[0]);
        $this->assertSame(['XL' => '84', 'M' => '82'], $detail->imageSizes);
        $this->assertSame('Yamaha MT-07 ABS', $detail->galleryAlt);
        $this->assertStringContainsString('rule=$_84.jpg', $detail->getImageUrl(0, 'XL') ?? '');
    }

    public function test_parse_html_extracts_shipping(): void
    {
        $detail = $this->parser->parseHtml($this->fixtureHtml, '/v/test');

        $this->assertNotNull($detail->shipping);
        $this->assertCount(2, $detail->shipping->carriers);
        $this->assertSame('dhl-nl', $detail->shipping->carriers[0]->carrierId);
        $this->assertCount(1, $detail->shipping->carriers[0]->labels);
        $this->assertSame('DHL', $detail->shipping->carriers[0]->labels[0]->carrierName);
        $this->assertSame('PICK_UP', $detail->shipping->carriers[0]->labels[0]->deliveryMethod);

        $this->assertNotNull($detail->shipping->deliveryType);
        $this->assertSame('Ophalen', $detail->shipping->deliveryType->attributeValueLabel);
    }

    public function test_parse_html_extracts_attributes(): void
    {
        $detail = $this->parser->parseHtml($this->fixtureHtml, '/v/test');

        $this->assertCount(5, $detail->attributes);

        $labels = array_map(fn (ListingDetailAttribute $a): string => $a->label, $detail->attributes);
        $this->assertContains('Adverteerder', $labels);
        $this->assertContains('Type motor', $labels);
        $this->assertContains('Bouwjaar', $labels);
        $this->assertContains('Kilometerstand', $labels);
        $this->assertContains('Opties', $labels);

        $optiesAttr = $detail->attributes[4];
        $this->assertSame('Opties', $optiesAttr->label);
        $this->assertSame('ABS, Handvatverwarming, LED Verlichting', $optiesAttr->value);
    }

    public function test_parse_html_extracts_traits(): void
    {
        $detail = $this->parser->parseHtml($this->fixtureHtml, '/v/test');

        $this->assertSame(['DAG_TOPPER_7DAYS', 'PACKAGE_PREMIUM'], $detail->traits);
    }

    public function test_parse_html_extracts_flags(): void
    {
        $detail = $this->parser->parseHtml($this->fixtureHtml, '/v/test');

        $this->assertSame('RegularPaid', $detail->adType);
        $this->assertFalse($detail->buyItNowEnabled);
        $this->assertFalse($detail->buyersProtectionAllowed);
        $this->assertFalse($detail->thinContent);
        $this->assertTrue($detail->isAutomotiveAd);
        $this->assertFalse($detail->isFreeAd);
        $this->assertTrue($detail->shippable);
    }

    public function test_parse_html_resolves_full_url_from_relative_path(): void
    {
        $detail = $this->parser->parseHtml(
            $this->fixtureHtml,
            '/v/motoren/motoren-yamaha/m2355451324-yamaha-mt-07-abs',
        );

        $this->assertSame(
            'https://www.marktplaats.nl/v/motoren/motoren-yamaha/m2355451324-yamaha-mt-07-abs',
            $detail->fullUrl,
        );
    }

    public function test_parse_html_keeps_absolute_url(): void
    {
        $detail = $this->parser->parseHtml(
            $this->fixtureHtml,
            'https://www.marktplaats.nl/v/motoren/motoren-yamaha/m2355451324',
        );

        $this->assertSame(
            'https://www.marktplaats.nl/v/motoren/motoren-yamaha/m2355451324',
            $detail->fullUrl,
        );
    }

    public function test_parse_html_throws_on_missing_config(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Could not extract __CONFIG__');

        $this->parser->parseHtml('<html><body>No config here</body></html>', '/v/test');
    }

    public function test_parse_html_throws_on_invalid_json(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Failed to decode listing __CONFIG__');

        $html = '<script>window.__CONFIG__ = {invalid json};</script>';
        $this->parser->parseHtml($html, '/v/test');
    }

    public function test_parse_html_with_unexpected_listing_shapes_does_not_throw_type_error(): void
    {
        $html = '<script>window.__CONFIG__ = '.json_encode([
            'listing' => [
                'itemId' => 123,
                'title' => true,
                'adType' => ['invalid'],
                'priceInfo' => 'invalid',
                'seller' => 'invalid',
                'category' => 'invalid',
                'stats' => 'invalid',
                'bidsInfo' => 'invalid',
                'shippingInformation' => 'invalid',
                'traits' => 'invalid',
                'flags' => 'invalid',
                'gallery' => 'invalid',
            ],
        ], JSON_THROW_ON_ERROR).';</script>';

        $detail = $this->parser->parseHtml($html, '/v/test');

        $this->assertSame('123', $detail->itemId);
        $this->assertSame('1', $detail->title);
        $this->assertNull($detail->adType);
        $this->assertNull($detail->priceInfo);
        $this->assertNull($detail->seller);
        $this->assertNull($detail->category);
        $this->assertNull($detail->stats);
        $this->assertNull($detail->bidsInfo);
        $this->assertNull($detail->shipping);
        $this->assertSame([], $detail->traits);
        $this->assertSame([], $detail->images);
        $this->assertFalse($detail->shippable);
    }

    public function test_extract_config_json_handles_multiline_script_block(): void
    {
        $html = <<<'HTML'
<script>
window.__CONFIG__ = {
  "listing": {
    "itemId": "m1",
    "title": "Test"
  }
};
</script>
HTML;

        $config = $this->parser->extractConfigJson($html);

        $this->assertSame('m1', $config['listing']['itemId']);
    }

    public function test_listing_detail_to_array_from_array_roundtrip(): void
    {
        $detail = $this->parser->parseHtml($this->fixtureHtml, '/v/test');
        $array = $detail->toArray();
        $restored = ListingDetail::fromArray($array);

        $this->assertSame($detail->itemId, $restored->itemId);
        $this->assertSame($detail->title, $restored->title);
        $this->assertSame($detail->description, $restored->description);
        $this->assertSame($detail->priceInfo?->priceCents, $restored->priceInfo?->priceCents);
        $this->assertSame($detail->seller?->id, $restored->seller?->id);
        $this->assertSame($detail->category?->id, $restored->category?->id);
        $this->assertSame($detail->stats?->viewCount, $restored->stats?->viewCount);
        $this->assertCount(count($detail->bidsInfo?->bids ?? []), $restored->bidsInfo?->bids ?? []);
        $this->assertCount(count($detail->images), $restored->images);
        $this->assertSame($detail->imageSizes, $restored->imageSizes);
        $this->assertSame($detail->galleryAlt, $restored->galleryAlt);
        $this->assertCount(count($detail->attributes), $restored->attributes);
        $this->assertSame($detail->traits, $restored->traits);
        $this->assertSame($detail->buyItNowEnabled, $restored->buyItNowEnabled);
        $this->assertSame($detail->isFreeAd, $restored->isFreeAd);
        $this->assertSame($detail->shippable, $restored->shippable);
    }

    public function test_extract_description_returns_null_on_missing(): void
    {
        $result = $this->parser->extractDescription('<html><body>No description</body></html>');

        $this->assertNull($result);
    }

    public function test_extract_attributes_returns_empty_on_missing(): void
    {
        $result = $this->parser->extractAttributes('<html><body>No attributes</body></html>');

        $this->assertSame([], $result);
    }
}
