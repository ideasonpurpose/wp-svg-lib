<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();
/**


/**
 * @covers \IdeasOnPurpose\WP\SVG
 */
final class ShortcodeTest extends TestCase
{
    protected function setUp(): void
    {
        $this->SVG = new SVG(__DIR__ . '/fixtures/svg');
        $this->SVG->init();
    }

    public function testEmbedSvg()
    {
        $actual = $this->SVG->svgShortcode(['arrow']);
        $expected = $this->SVG->lib['arrow']->content->clean;

        $this->assertEquals($expected, $actual);
        $this->assertStringContainsString('<svg', $actual);
    }
}
