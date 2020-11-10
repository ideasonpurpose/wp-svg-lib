<?php

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\SVG;

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
 * @covers \IdeasOnPurpose\SVG
 */

final class SVGTest extends TestCase
{
    protected function setUp(): void
    {
        $this->SVG = new SVG(__DIR__ . '/fixtures');
    }

    public function testWorking()
    {
        $this->assertEquals(true, true);
    }

    public function testLib()
    {
        $reflector = new ReflectionClass($this->SVG);
        $prop = $reflector->getProperty('lib');
        $prop->setAccessible(true);
        $lib = $prop->getValue($this->SVG);

        // Loaded from filesystem
        $this->assertArrayHasKey('arrow', $lib);
        $this->assertStringEqualsFile(__DIR__ . '/fixtures/arrow.svg', $lib['arrow'] . "\n");

        // Embedded in Class
        $this->assertArrayHasKey('test', $lib);
    }

    /**
     * This test confirms that the original implementation
     * of $SVG->get() still works correctly. This method was
     * moved renamed to $SVG->use()
     */
    public function testLegacyGet()
    {
        $arrow = $this->SVG->get('arrow');
        $this->assertStringContainsStringIgnoringCase('use xlink:href', $arrow);

        $nope = $this->SVG->get('nope');
        $this->assertStringContainsStringIgnoringCase('SVG Lib Error', $nope);
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
        ob_start();
        $this->SVG->dumpSymbols();
        $dump = ob_get_clean();
        $this->assertStringContainsString('<!-- NO SVGs IN USE -->', $dump);

        /**
         * No user logged in, no SVGs in use
         */
        $user_logged_in = false;
        ob_start();
        $this->SVG->dumpSymbols();
        $dump = ob_get_clean();
        $this->assertEquals('', $dump);
    }

    /**
     *
     * Test dumpSymbols with SVGs in use
     * dumpSymbols is called from the wp_footer hook
     */
    public function test_dumpSymbolsUseSVGs()
    {
        global $user_logged_in;

        $this->SVG->get('arrow');

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
}
