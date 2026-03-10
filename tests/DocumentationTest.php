<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Tests;

use PHPUnit\Framework\TestCase;

class DocumentationTest extends TestCase
{
    public function test_readme_quick_start_uses_current_search_query_arguments(): void
    {
        $readme = (string) file_get_contents(__DIR__.'/../README.md');

        $this->assertStringContainsString("query: 'bureau'", $readme);
        $this->assertStringNotContainsString('l1CategoryId:', $readme);
    }
}
