<?php

namespace IdeasOnPurpose;

use PHPUnit\Framework\TestCase;

/**
 * Mock add_action
 */
function add_action($hook, $action)
{
}

/**
 * Mock is_user_logged_in
 * Returns the $user_logged_in global var
 * set that var to toggle the result
 */
function is_user_logged_in()
{
    global $user_logged_in;
    return !!$user_logged_in;
}

/**
 * Mock error_log
 */
function error_log($err)
{
}

/**
 * @covers \IdeasOnPurpose\SVG
 */
final class SVGTest extends TestCase
{
    protected function setUp(): void
    {
        $this->SVG = new SVG(__DIR__ . '/fixtures/svg');
    }

    public function testLib()
    {
        $reflector = new \ReflectionClass($this->SVG);
        $prop = $reflector->getProperty('lib');
        $prop->setAccessible(true);
        $lib = $prop->getValue($this->SVG);

        // Loaded from filesystem
        $this->assertArrayHasKey('arrow', $lib);
        $this->assertStringEqualsFile(__DIR__ . '/fixtures/svg/arrow.svg', $lib['arrow'] . "\n");
    }

    public function testNoLib()
    {
        $nope = new SVG(__DIR__ . '/fixtures/svg/arrow.svg');
        $this->expectOutputRegex('/><\/div>/');
        $this->assertCount(0, $nope->debug());
    }

    public function testEmbed()
    {
        $arrow = $this->SVG->embed('arrow');
        $this->assertStringContainsString('<svg', $arrow);

        $this->expectOutputRegex('/<!-- SVG Lib Error/');
        $nope = $this->SVG->embed('nope');
        $this->assertNull($nope);
    }

    public function testUse()
    {
        $arrow = $this->SVG->use('arrow');
        $this->assertStringContainsString('<svg', $arrow);
        $this->assertStringContainsStringIgnoringCase('use xlink:href', $arrow);

        $this->expectOutputRegex('/<!-- SVG Lib Error/');
        $nope = $this->SVG->use('nope');
        $this->assertNull($nope);
    }

    /**
     * This test confirms that the original implementation
     * of $SVG->get() still works correctly. This method was
     * moved renamed to $SVG->use()
     */
    public function testLegacyGet()
    {
        ob_start();
        $arrow = $this->SVG->get('arrow');
        $dump = ob_get_clean();
        $this->assertStringContainsStringIgnoringCase('use xlink:href', $arrow);
        $this->assertStringContainsString('get method is deprecated', $dump);

        $this->expectOutputRegex('/<!-- SVG Lib Error/');
        $nope = $this->SVG->get('nope');
        $this->assertNull($nope);
    }

    /**
     * Test dumpSymbols with no SVGs in use
     * dumpSymbols is called from the wp_footer hook
     */
    public function test_dumpSymbolsNoSVGs()
    {
        global $user_logged_in;

        /**
         * User logged in, no SVGs in use
         */
        $user_logged_in = true;
        $this->expectOutputRegex('/<!-- NO SVGs IN USE/');
        $this->SVG->dumpSymbols();

        /**
         * No user logged in, no SVGs in use
         */
        $user_logged_in = false;
        $this->expectOutputString('');
        $this->SVG->dumpSymbols();
    }

    /**
     *
     * Test dumpSymbols with SVGs in use
     * dumpSymbols is called from the wp_footer hook
     */
    public function test_dumpSymbolsUseSVGs()
    {
        global $user_logged_in;

        $this->SVG->use('arrow');

        $user_logged_in = false;
        ob_start();
        $this->SVG->dumpSymbols();
        $dump = ob_get_clean();
        $this->assertStringContainsString(
            "<svg xmlns='http://www.w3.org/2000/svg' style='display: none;'>",
            $dump
        );
        $this->assertStringContainsString('<symbol ', $dump);

        /**
         * should be the same if user is logged in
         */
        $user_logged_in = true;
        ob_start();
        $this->SVG->dumpSymbols();
        $dump = ob_get_clean();
        $this->assertStringContainsString(
            "<svg xmlns='http://www.w3.org/2000/svg' style='display: none;'>",
            $dump
        );
        $this->assertStringContainsString('<symbol ', $dump);
    }

    /**
     * Old, likely abandoned methods only here to pump coverage numbers
     *
     */
    public function testDebug()
    {
        ob_start();
        $this->SVG->debug();
        $dump = ob_get_clean();
        $this->assertStringContainsString('<style>', $dump);

        ob_start();
        $this->SVG->directory();
        $dump = ob_get_clean();
        $this->assertStringContainsString('directory method is deprecated', $dump);
    }

    public function testStaticSVGs()
    {
        require 'fixtures/StaticSVG.php';

        $static = new StaticSVG(__DIR__ . '/fixtures/svg');
        $svg = $static->arrow;
        $this->assertStringContainsString('<svg', $svg);
    }
}
