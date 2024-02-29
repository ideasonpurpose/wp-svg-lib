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
 */
final class SVGTest extends TestCase
{
    // public $SVG;

    public function setUp(): void
    {
        // $this->SVG = new SVG(__DIR__ . '/fixtures/svg');
        // $this->SVG->init();
    }

    public function beforeEach()
    {
        global $transients;
        $transients = [];
    }

    public function testLib()
    {
        $SVG = new SVG(__DIR__ . '/fixtures/svg');
        $SVG->init();
        $lib = $SVG->lib;
        // Loaded from filesystem
        $this->assertArrayHasKey('arrow', $lib);
    }

    public function testInit_transient()
    {
        global $get_transient;

        $SVG = new SVG(__DIR__ . '/fixtures/svg2');
        $get_transient[$SVG->transient] = ['hello'];

        $SVG->WP_DEBUG = false;
        $SVG->init();

        $this->assertArrayHasKey('_from_transient', $SVG->lib);
        $this->assertArrayHasKey('_processing_time', $SVG->lib);
        $this->assertTrue($SVG->lib['_from_transient']);
    }

    public function testEmbed()
    {
        $SVG = new SVG(__DIR__ . '/fixtures/svg');

        $arrow = $SVG->embed('arrow');
        $this->assertStringContainsString('<svg', $arrow);

        // $nope = $this->SVG->embed('nope');
        // $this->assertNull($nope);

        /**
         * Make sure _from_transient and _processing_time don't respond
         */
        // $nope = $this->SVG->embed('_from_transient');
        // $this->assertNull($nope);

        // $nope = $this->SVG->embed('_processing_time');
        // $this->assertNull($nope);

        // $actual = ob_get_contents();
        // $actual = $this->output();

        // $this->assertStringContainsString('<!-- SVG Lib Error', $actual);
        // $this->expectOutputRegex('/<!-- SVG Lib Error/');
    }

    public function testEmbedError()
    {
        $svg = $this->getMockBuilder(\IdeasOnPurpose\WP\SVG::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetch'])
            ->getMock();

        $svg->WP_DEBUG = true;
        $err_message = 'PHPUnit Error';
        $err = new \WP_ERROR(123, $err_message);
        $svg->method('fetch')->willReturn($err);

        $actual = $svg->embed('mocked');
        $this->assertStringContainsString($err_message, $actual);
        $this->assertStringContainsString('<svg', $actual);
    }

    /**
     * Coverage until we pick a final name: getSVG() vs fetch()
     */
    public function testGetSvg()
    {
        $svg = $this->getMockBuilder('\IdeasOnPurpose\WP\SVG')
            ->disableOriginalConstructor()
            ->onlyMethods(['fetch'])
            ->getMock();

        $svg->expects($this->once())->method('fetch');

        /** @var \IdeasOnPurpose\WP\SVG $svg */
        $svg->getSVG('arrow');
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

        $this->expectOutputRegex('/get method is deprecated/');
        $svg->method('use')->willReturn($expected);

        /** @var \IdeasOnPurpose\WP\SVG $svg */
        $actual = $svg->get('arrow');
        $this->assertEquals($expected, $actual);
    }

    public function testDebug()
    {
        $SVG = new SVG(__DIR__ . '/fixtures/svg');
        $SVG->init();

        $this->expectOutputRegex('/<div/');
        $lib = $SVG->debug();
        $this->assertGreaterThan(0, $lib);

        $actual = ob_get_contents();

        $this->assertStringContainsString('<style>', $actual);
        $this->assertStringContainsString('</div>', $actual);
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
        $actual = ob_get_contents();

        $this->assertStringNotContainsString('<style>', $actual);
        $this->assertStringNotContainsString('</div>', $actual);
    }

    public function testNormalizeEmptyKey(): void
    {
        $SVG = new SVG(__DIR__ . '/fixtures/svg');
        $actual = $SVG->normalizeKey('');
        $this->assertEmpty($actual);
    }

    public function testNormalizeNullKey(): void
    {
        $SVG = new SVG(__DIR__ . '/fixtures/svg');
        $actual = $SVG->normalizeKey(false);
        $this->assertEmpty($actual);
    }

    public function testNormalizeFalseKey(): void
    {
        $SVG = new SVG(__DIR__ . '/fixtures/svg');
        $actual = $SVG->normalizeKey(null);
        $this->assertEmpty($actual);
    }

    /**
     * Check that set_query_var is called when there are any SVGs in the library
     */
    public function testValidateAttributes_good(): void
    {
        $SVG = new SVG(__DIR__ . '/fixtures/svg');

        $classList = 'red green blue';
        $attributes = ['width' => 'AUTO', 'height' => 123, 'class' => $classList];

        $actual = $SVG->validateAttributes($attributes);

        $this->assertArrayHasKey('width', $actual);
        $this->assertArrayHasKey('height', $actual);
        $this->assertArrayHasKey('class', $actual);
        $this->assertEquals('auto', $actual['width']);
        $this->assertEquals($classList, $actual['class']);
    }

    /**
     * Check that set_query_var is called when there are any SVGs in the library
     */
    public function testQueryVar()
    {
        global $query_var;

        $SVG = new SVG(__DIR__ . '/fixtures/svg');
        $SVG->init();

        $SVG->registerQueryVar();
        $actual = array_pop($query_var);

        $this->assertEquals('SVG', $actual['key']);

        // reset
        $query_var = [];

        $emptyLib = new SVG(__DIR__ . '/fixtures/svg/arrow.svg');
        $emptyLib->registerQueryVar();
        $actual = array_pop($query_var);
        $this->assertNull($actual);
    }

    public function tesWrapSVG(): void
    {
        $SVG = new SVG(__DIR__ . '/fixtures/svg');
        $actual = $SVG->wrapSvg('', ['viewBox' => '3 4 5 6', 'class' => 'dog', 'width' => 44]);
        $this->assertStringContainsString('<svg class="dog" width="44"', $actual);
        $this->assertStringContainsString('xmlns=', $actual);
        $this->assertStringContainsString('http://www.w3.org/2000/svg', $actual);
    }
    public function testWrapSVGWrongViewBoxCase(): void
    {
        $SVG = new SVG(__DIR__ . '/fixtures/svg');
        $actual = $SVG->wrapSvg('', ['VIEWBOX' => '3 4 5 6', 'id' => 'foo']);
        $this->assertStringNotContainsString('viewBox', $actual);
        $this->assertStringNotContainsString('VIEWBOX', $actual);
        $this->assertStringContainsString('id', $actual);
        $this->assertStringContainsString('http://www.w3.org/2000/svg', $actual);
    }

    public function testWrapSVGExtraAttributes(): void
    {
        $SVG = new SVG(__DIR__ . '/fixtures/svg');
        $actual = $SVG->wrapSvg('', ['frog' => 'kermit', 'color' => 'green', 'width' => '55']);
        $this->assertStringNotContainsString('frog', $actual);
        $this->assertStringNotContainsString('kermit', $actual);
        $this->assertStringNotContainsString('color', $actual);
        $this->assertStringNotContainsString('green', $actual);
        $this->assertStringContainsString('width', $actual);
        // $this->assertStringContainsString('viewBox', $actual);
        $this->assertStringContainsString('http://www.w3.org/2000/svg', $actual);
    }

    public function testSvgOpenTagEnforceOrder(): void
    {
        $SVG = new SVG(__DIR__ . '/fixtures/svg');

        $actual = $SVG->wrapSvg('', [
            'height' => 12,
            'viewBox' => '0 2 11 22',
            'class' => 'bar',
            'width' => '55',
            'id' => 'foo',
        ]);
        $this->assertMatchesRegularExpression(
            '/svg.*id.*class.*width.*height.*viewBox.*xmlns/',
            $actual
        );
    }
}
