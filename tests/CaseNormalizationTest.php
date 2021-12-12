<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

/**
 * @covers \IdeasOnPurpose\WP\SVG
 */
final class CaseNormalizationTest extends TestCase
{
    protected function setUp(): void
    {
        $this->SVG = new SVG(__DIR__ . '/fixtures/svg');
        $this->SVG->is_debug = true;
    }

    /**
     * Test magic methods for embedding SVGs
     *
     * Exact matches should work when quoted.
     * All names should also be directly callable using camelCase
     */
    public function testMagicMethodsFound()
    {
        $arrow = $this->SVG->arrow;
        $this->assertStringContainsString('<svg', $arrow);
    }

    public function testMagicMethodsNotFound()
    {
        $nope = $this->SVG->nope;
        $this->assertNull($nope);
        $this->expectOutputRegex('/<!-- SVG Lib Error/');
    }

    public function testCamelCase()
    {
        $svg = $this->SVG->camelCase;
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->embed('camelCase');
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->embed('camel-case');
        $this->assertNull($svg);

        $svg = $this->SVG->camelcase; // all-lowercase request fails
        $this->assertNull($svg);

        $this->expectOutputRegex('/<!-- SVG Lib Error/');
    }

    public function testAllCaps()
    {
        $svg = $this->SVG->ALLCAPS;
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->embed('ALLCAPS');
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->allcaps;
        $this->assertNull($svg);

        $svg = $this->SVG->allCaps;
        $this->assertNull($svg);

        $svg = $this->SVG->Allcaps;
        $this->assertNull($svg);

        $this->expectOutputRegex('/<!-- SVG Lib Error/');
    }

    public function testDashCase()
    {
        $svg = $this->SVG->dashCase;
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->embed('dash-case');
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->embed('dashCase');
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->dashcase;
        $this->assertNull($svg);

        $this->expectOutputRegex('/<!-- SVG Lib Error/');
    }

    public function testDotCase()
    {
        $svg = $this->SVG->embed('dot.case');
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->dotcase;
        $this->assertNull($svg);

        $svg = $this->SVG->dotCase;
        $this->assertNull($svg);

        $this->expectOutputRegex("/<!-- SVG Lib Error: The key 'dotCase'/");
    }

    public function testSnakeCase()
    {
        $svg = $this->SVG->snakeCase;
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->snake_case;
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->embed('snake_case');
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->snake_case;
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->snakecase;
        $this->assertNull($svg);

        $this->expectOutputRegex('/<!-- SVG Lib Error/');
    }

    public function testSpaces()
    {
        $svg = $this->SVG->omgSpaces;
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->embed('omg spaces');
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->omgspaces;
        $this->assertNull($svg);

        $this->expectOutputRegex('/<!-- SVG Lib Error/');
    }

    public function testsubdir()
    {
        $svg = $this->SVG->sub__dir__nestedFile;
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->embed('sub/dir/nested-file');
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->subDirNestedFile;
        $this->assertNull($svg);

        $this->expectOutputRegex('/<!-- SVG Lib Error/');
    }
}
