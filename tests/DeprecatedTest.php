<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();
/**


/**
 * @covers \IdeasOnPurpose\WP\SVG
 * @covers \IdeasOnPurpose\WP\Deprecated\Directory
 * @covers \IdeasOnPurpose\WP\Deprecated\DumpSymbols
 * @covers \IdeasOnPurpose\WP\Deprecated\Get
 */
final class DeprecatedTest extends TestCase
{
    public $SVG;

    protected function setUp(): void
    {
        $this->SVG = new SVG(__DIR__ . '/fixtures/svg');
        $this->SVG->init();
    }

    public function testDirectoryDeprecated()
    {
        $this->SVG->directory();
        $this->expectOutputRegex('/directory method is deprecated/');
        $actual = ob_get_contents();

        $this->assertStringContainsString('<style>', $actual);
        $this->assertStringContainsString('</div>', $actual);
    }

    /**
     * Test dumpSymbols with no SVGs in use
     * Logged in message only, no debug output
     */
    public function test_dumpSymbolsNoSVGs()
    {
        global $is_user_logged_in;

        $is_user_logged_in = true;
        $this->SVG->is_debug = false;

        $this->expectOutputRegex('/<!-- NO SVGs IN USE/');
        $this->SVG->dumpSymbols();

        $actual = ob_get_contents();

        $this->assertStringNotContainsString('<!-- SVG::dumpSymbols call stack', $actual);
    }

    /**
     * Test dumpSymbols with no SVGs in use
     * Logged in message and debug output
     */
    public function test_dumpSymbolsNoSVGsDebug()
    {
        global $is_user_logged_in;

        $is_user_logged_in = true;
        $this->SVG->is_debug = true;

        $this->expectOutputRegex('/<!-- NO SVGs IN USE/');
        $this->SVG->dumpSymbols();

        $actual = ob_get_contents();

        $this->assertStringContainsString('<!-- SVG::dumpSymbols call stack', $actual);
    }

    /**
     *
     * Test dumpSymbols with SVGs in use
     */
    public function test_dumpSymbolsUseSVGs()
    {
        $this->expectOutputRegex('/<symbol.*viewBox/');
        $this->SVG->use('arrow');
        $this->SVG->dumpSymbols();

        $actual = ob_get_contents();
        $this->assertStringContainsString('display: none', $actual);
        $this->assertStringContainsString('<svg ', $actual);
    }
}
