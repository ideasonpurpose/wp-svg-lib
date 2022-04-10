<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();
/**

 * Mock error_log
 */
function error_log($err)
{
}

/**
 * @covers \IdeasOnPurpose\WP\SVG
 * @covers \IdeasOnPurpose\WP\SVG\Shortcodes
 */
final class SVGTest extends TestCase
{
    protected function setUp(): void
    {
        $this->SVG = new SVG(__DIR__ . '/fixtures/svg');
        $this->SVG->init();
    }

    public function testLib()
    {
        $lib = $this->SVG->lib;
        // Loaded from filesystem
        $this->assertArrayHasKey('arrow', $lib);
        $this->assertStringEqualsFile(
            __DIR__ . '/fixtures/svg/arrow.svg',
            $lib['arrow']->content->raw . "\n"
        );
    }

    public function testInit_transient()
    {
        global $transients;

        $this->SVG->WP_DEBUG = false;
        $this->SVG->transient = 'transient_id';
        $transients = [$this->SVG->transient => []];

        $this->SVG->init();

        $this->assertArrayHasKey('_from_transient', $this->SVG->lib);
        $this->assertArrayHasKey('_processing_time', $this->SVG->lib);
        $this->assertTrue($this->SVG->lib['_from_transient']);
    }

    public function testEmbed()
    {
        $arrow = $this->SVG->embed('arrow');
        $this->assertStringContainsString('<svg', $arrow);

        $nope = $this->SVG->embed('nope');
        $this->assertNull($nope);

        /**
         * Make sure _from_transient and _processing_time
         */
        $nope = $this->SVG->embed('_from_transient');
        $this->assertNull($nope);

        $nope = $this->SVG->embed('_processing_time');
        $this->assertNull($nope);

        $actual = $this->getActualOutput();
        $this->assertStringContainsString('<!-- SVG Lib Error', $actual);
        $this->expectOutputRegex('/<!-- SVG Lib Error/');
    }

    public function testUse()
    {
        $arrow_svg = $this->SVG->use('arrow.svg');
        $nope = $this->SVG->use('not-a-file');
        $inUse = $this->SVG->inUse;
        $actual = $this->getActualOutput();

        $this->assertStringContainsString('<!-- SVG Lib Error', $actual);
        $this->assertStringContainsStringIgnoringCase('use xlink:href', $arrow_svg);
        $this->assertNull($nope);
        $this->assertCount(1, $inUse);
        $this->expectOutputRegex('/<!-- SVG Lib Error/');
    }

    /**
     * This test confirms that the original implementation
     * of $SVG->get() still works correctly.
     *
     * This test only checks for a deprecation notice and that the mocked
     * $SVG::use method is called
     */
    public function testLegacyGet()
    {
        $expected = 'use was called';
        $svg = $this->getMockBuilder('\IdeasOnPurpose\WP\SVG')
            ->disableOriginalConstructor()
            ->onlyMethods(['use'])
            ->getMock();
        $svg->method('use')->willReturn($expected);

        /** @var \IdeasOnPurpose\WP\SVG $svg */
        $actual = $svg->get('arrow');
        $this->assertEquals($expected, $actual);
        $this->expectOutputRegex('/get method is deprecated/');
    }

    /**
     * Test dumpSymbols with no SVGs in use
     * Logged in message only, no debug output
     */
    public function test_dumpSymbolsNoSVGs()
    {
        global $is_user_logged_in;

        $is_user_logged_in = true;
        $this->SVG->WP_DEBUG = false;
        $this->SVG->dumpSymbols();

        $actual = $this->getActualOutput();

        $this->assertStringNotContainsString('<!-- SVG::dumpSymbols call stack', $actual);
        $this->expectOutputRegex('/<!-- NO SVGs IN USE/');
    }

    /**
     * Test dumpSymbols with no SVGs in use
     * Logged in message and debug output
     */
    public function test_dumpSymbolsNoSVGsDebug()
    {
        global $is_user_logged_in;

        $is_user_logged_in = true;
        $this->SVG->WP_DEBUG = true;
        $this->SVG->dumpSymbols();

        $actual = $this->getActualOutput();

        $this->assertStringContainsString('<!-- SVG::dumpSymbols call stack', $actual);
        $this->expectOutputRegex('/<!-- NO SVGs IN USE/');
    }

    /**
     *
     * Test dumpSymbols with SVGs in use
     */
    public function test_dumpSymbolsUseSVGs()
    {
        $this->SVG->use('arrow');
        $this->SVG->dumpSymbols();

        $dump = $this->getActualOutput();
        $this->assertStringContainsString('display: none', $dump);
        $this->assertStringContainsString('<svg ', $dump);
        $this->expectOutputRegex('/<symbol.*viewBox/');
    }

    public function testDebug()
    {
        $lib = $this->SVG->debug();
        $this->assertGreaterThan(0, $lib);

        $actual = $this->getActualOutput();
        $this->assertStringContainsString('<style>', $actual);
        $this->assertStringContainsString('</div>', $actual);
        $this->expectOutputRegex('/<div/');
    }

    public function testDebugEmptyLib()
    {
        $nope = new SVG(__DIR__ . '/fixtures/svg/arrow.svg');

        /**
         * SVG::debug() returns the current instances 'lib' property,
         * so we can count that
         */
        $this->assertCount(0, $nope->debug());

        /**
         * SVG::debug should not dump CSS rules and the debug container
         * when there is nothing registered in $VG->lib
         */
        $actual = $this->getActualOutput();
        $this->assertStringNotContainsString('<style>', $actual);
        $this->assertStringNotContainsString('</div>', $actual);
    }

    public function testDirectoryDeprecated()
    {
        $this->SVG->directory();
        $this->expectOutputRegex('/directory method is deprecated/');
        $actual = $this->getActualOutput();
        $this->assertStringContainsString('<style>', $actual);
        $this->assertStringContainsString('</div>', $actual);
    }

    public function testStaticSVGs()
    {
        /**
         * This file contains the StaticSVG class which extends SVG
         * and registers a public static $arrowStatic variable which contains
         * a raw SVG string.
         *
         * This is how the library worked initially, and has been marked
         * deprecated. Tests are to make sure it doesn't break on existing
         * legacy code.
         */
        require 'fixtures/StaticSVG.php';

        $static = new StaticSVG(__DIR__ . '/fixtures/svg');
        $static->init();

        $svg = $static->arrowStatic;
        $this->assertStringContainsString('<svg', $svg);
    }

    /**
     * Check that set_query_var is called when there are any SVGs in the library
     */
    public function testQueryVar()
    {
        global $query_var;

        $this->SVG->registerQueryVar();
        $actual = array_pop($query_var);

        $this->assertEquals('SVG', $actual['key']);

        // reset
        $query_var = [];

        $emptyLib = new SVG(__DIR__ . '/fixtures/svg/arrow.svg');
        $emptyLib->registerQueryVar();
        $actual = array_pop($query_var);
        $this->assertNull($actual);
    }

    public function testCleanSvg()
    {
        $actual = $this->SVG->cleanSvg('nope');
        $this->assertNull($actual);
        $output = $this->getActualOutput();

        $this->expectOutputRegex('/<!-- SVG Lib Error/');

        $this->SVG->attributes = ['width' => 123];
        $this->SVG->cleanSvg('arrow');
        $this->assertObjectHasAttribute('clean_json', $this->SVG->lib['arrow']->_links);
    }

    public function testCleanSvg_error()
    {
        $this->SVG->lib['bad'] = (object) [
            'src' => 'fake/file/path.svg',
            'content' => (object) ['raw' => '<svg><g>'],
        ];
        $this->SVG->cleanSvg('bad');
        $this->assertObjectHasAttribute('errors', $this->SVG->lib['bad']);
    }
}
