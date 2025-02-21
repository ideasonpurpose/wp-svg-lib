<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

#[CoversClass(\IdeasOnPurpose\WP\SVG::class)]
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
        $this->SVG->WP_DEBUG = false;

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
        $this->SVG->WP_DEBUG = true;

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

    public function test_hasSVG()
    {
        /** @var SVG|MockObject $svg */
        $svg = $this->getMockBuilder('\IdeasOnPurpose\WP\SVG')
            ->disableOriginalConstructor()
            ->onlyMethods(['exists'])
            ->getMock();

        $svg->expects($this->once())->method('exists')->with($this->equalTo('arrow'));

        $svg->hasSVG('arrow');
    }
}
