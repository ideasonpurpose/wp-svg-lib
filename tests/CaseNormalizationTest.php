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
    public $SVG;

    public function setUp(): void
    {
        $this->SVG = new SVG(__DIR__ . '/fixtures/svg');
        $this->SVG->init();
        $this->SVG->WP_DEBUG = true;
    }

    public function beforeEach()
    {
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

    // public function testMagicMethodsNotFound()
    // {
    //     $nope = $this->SVG->nope;
    //     $this->assertNull($nope);
    //     $this->expectOutputRegex('/<!-- SVG Lib Error/');
    //     $actual = $this->output();
    // }

    public function testCamelCase()
    {
        $expected = 'camelCase';

        $actual = $this->SVG->normalizeKey('camelCase');
        $this->assertEquals($expected, $actual);

        $actual = $this->SVG->normalizeKey('camel-case');
        $this->assertEquals($expected, $actual);

        $actual = $this->SVG->normalizeKey('camelcase');
        $this->assertNotEquals($expected, $actual);
    }

    public function testAllCaps()
    {
        $expected = 'ALLCAPS';

        // TODO: Special handling of ALLCAPS?
        // Some libraries return 'allcaps' others 'aLLCAPS'
        // so, filenames can NOT currently be allcaps

        $actual = $this->SVG->normalizeKey('ALLCAPS');
        $this->assertNotEquals($expected, $actual);

        $actual = $this->SVG->normalizeKey('allCaps');
        $this->assertNotEquals($expected, $actual);

        $actual = $this->SVG->normalizeKey('allcaps');
        $this->assertNotEquals($expected, $actual);

        $actual = $this->SVG->normalizeKey('Allcaps');
        $this->assertNotEquals($expected, $actual);
    }

    public function testDashCase()
    {
        $expected = 'dashCase';

        $actual = $this->SVG->normalizeKey('dash-case');
        $this->assertEquals($expected, $actual);

        $actual = $this->SVG->normalizeKey('dashCase');
        $this->assertEquals($expected, $actual);

        $actual = $this->SVG->normalizeKey('dashcase');
        $this->assertNotEquals($expected, $actual);
    }

    public function testDotCase()
    {
        $expected = 'dotCase';

        $actual = $this->SVG->normalizeKey('dot-case');
        $this->assertEquals($expected, $actual);

        $actual = $this->SVG->normalizeKey('dot.case');
        $this->assertNotEquals($expected, $actual);

        $actual = $this->SVG->normalizeKey('dotcase');
        $this->assertNotEquals($expected, $actual);
    }

    public function testSnakeCase()
    {
        $expected = 'snakeCase';

        $actual = $this->SVG->normalizeKey('snakeCase');
        $this->assertEquals($expected, $actual);

        $actual = $this->SVG->normalizeKey('snake_case');
        $this->assertEquals($expected, $actual);

        $actual = $this->SVG->normalizeKey('snake-case');
        $this->assertEquals($expected, $actual);
    }

    public function testSpaces()
    {
        $expected = 'omgSpaces';

        $actual = $this->SVG->normalizeKey('omg spaces');
        $this->assertEquals($expected, $actual);
    }

    public function testsubdir()
    {
        $expected = 'sub__dir__nestedFile';

        $actual = $this->SVG->normalizeKey('sub__dir__nestedFile');
        $this->assertEquals($expected, $actual);

        $actual = $this->SVG->normalizeKey('sub/dir/nested-file');
        $this->assertEquals($expected, $actual);

        $actual = $this->SVG->normalizeKey('subDirNestedFile');
        $this->assertNotEquals($expected, $actual);

        // $svg = $this->SVG->sub__dir__nestedFile;
        // $this->assertStringContainsString('<svg', $svg);

        // $svg = $this->SVG->embed('sub/dir/nested-file');
        // $this->assertStringContainsString('<svg', $svg);

        // $svg = $this->SVG->subDirNestedFile;
        // $this->assertNull($svg);

        // $this->expectOutputRegex('/<!-- SVG Lib Error/');
    }

    public function testDirectoryRoundtrip()
    {
        // Keys with directory names need to be idempotent:
        //'social/icon' should be 'social__icon'
        // 'social__icon' should also be 'social__icon'
        // Each of these checks normalized keys and double-normalized keys

        $key = 'social/icon.svg';
        $expected = 'social__icon';
        $actual = $this->SVG->normalizeKey($key);
        $actual2 = $this->SVG->normalizeKey($actual);
        $this->assertEquals($expected, $actual);
        $this->assertEquals($actual2, $actual);

        $key = 'social__icon';
        $expected = 'social__icon';
        $actual = $this->SVG->normalizeKey($key);
        $actual2 = $this->SVG->normalizeKey($actual);
        $this->assertEquals($expected, $actual);
        $this->assertEquals($actual2, $actual);

        $key = 'dash-name/camelCase.svg';
        $expected = 'dashName__camelCase';
        $actual = $this->SVG->normalizeKey($key);
        $actual2 = $this->SVG->normalizeKey($actual);
        $this->assertEquals($expected, $actual);
        $this->assertEquals($actual2, $actual);

        $key = 'dash-name/dash-name.svg';
        $expected = 'dashName__dashName';
        $actual = $this->SVG->normalizeKey($key);
        $actual2 = $this->SVG->normalizeKey($actual);
        $this->assertEquals($expected, $actual);
        $this->assertEquals($actual2, $actual);

        $key = 'dashName__dashName';
        $expected = 'dashName__dashName';
        $actual = $this->SVG->normalizeKey($key);
        $actual2 = $this->SVG->normalizeKey($actual);
        $this->assertEquals($expected, $actual);
        $this->assertEquals($actual2, $actual);
    }
}
