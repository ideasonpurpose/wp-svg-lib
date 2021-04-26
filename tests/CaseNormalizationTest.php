<?php

namespace IdeasOnPurpose;

use PHPUnit\Framework\TestCase;

/**
 * @covers \IdeasOnPurpose\SVG
 */
final class CaseNormalizationTest extends TestCase
{
    protected function setUp(): void
    {
        $this->SVG = new SVG(__DIR__ . '/fixtures/svg');
    }

    /**
     * Test magic methods for embedding SVGs
     */
    public function testMagicMethodsFound()
    {
        $arrow = $this->SVG->arrow;
        $this->assertStringContainsString('<svg', $arrow);
    }

    public function testMagicMethodsNotFound()
    {
        $this->expectOutputRegex('/<!-- SVG Lib Error/');
        $nope = $this->SVG->nope;
        $this->assertNull($nope);
    }

    public function testCamelCase()
    {
        $svg = $this->SVG->camelCase;
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->embed('camelCase');
        $this->assertStringContainsString('<svg', $svg);

        $this->expectOutputRegex('/<!-- SVG Lib Error/');
        $svg = $this->SVG->camelcase;
        $this->assertNull($svg);
    }

    public function testAllCaps()
    {
        $svg = $this->SVG->ALLCAPS;
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->embed('ALLCAPS');
        $this->assertStringContainsString('<svg', $svg);

        $this->expectOutputRegex('/<!-- SVG Lib Error/');
        $svg = $this->SVG->allcaps;
        $this->assertNull($svg);

        $svg = $this->SVG->Allcaps;
        $this->assertNull($svg);
    }

    public function testDashCase()
    {
        $svg = $this->SVG->dashCase;
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->embed('dash-case');
        $this->assertStringContainsString('<svg', $svg);

        $this->expectOutputRegex('/<!-- SVG Lib Error/');
        $svg = $this->SVG->dashcase;
        $this->assertNull($svg);
    }

    public function testDotCase()
    {
        $svg = $this->SVG->embed('dot.case');
        $this->assertStringContainsString('<svg', $svg);

        $this->expectOutputRegex("/<!-- SVG Lib Error: The key 'dotcase'/");
        $svg = $this->SVG->dotcase;
        $this->assertNull($svg);

        $this->expectOutputRegex("/<!-- SVG Lib Error: The key 'dotCase'/");
        $svg = $this->SVG->dotCase;
        $this->assertNull($svg);
    }

    public function testSnakeCase()
    {
        $svg = $this->SVG->snakeCase;
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->snake_case;
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->embed('snake_case');
        $this->assertStringContainsString('<svg', $svg);

        $this->expectOutputRegex('/<!-- SVG Lib Error/');
        $svg = $this->SVG->snakecase;
        $this->assertNull($svg);
    }

    public function testSpaces()
    {
        $svg = $this->SVG->omgSpaces;
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->embed('omg spaces');
        $this->assertStringContainsString('<svg', $svg);

        $this->expectOutputRegex('/<!-- SVG Lib Error/');
        $svg = $this->SVG->omgspaces;
        $this->assertNull($svg);
    }

    public function testsubdir()
    {
        $svg = $this->SVG->sub__dir__nestedFile;
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->embed('sub/dir/nested-file');
        $this->assertStringContainsString('<svg', $svg);

        $this->expectOutputRegex('/<!-- SVG Lib Error/');
        $svg = $this->SVG->subDirNestedFile;
        $this->assertNull($svg);
    }
}
