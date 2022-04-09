<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

/**
 * @covers \IdeasOnPurpose\WP\SVG
 */
final class ExtensionHandlingTest extends TestCase
{
    protected function setUp(): void
    {
        $this->SVG = new SVG(__DIR__ . '/fixtures/svg/extensions');
        $this->SVG->init();
    }

    public function testEmbedWithExtension()
    {
        $svg = $this->SVG->embed('arrow');
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->embed('arrow.svg');
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->embed('diff.svg');
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->embed('diff.svg.svg');
        $this->assertStringContainsString('<g fill="pink"', $svg);
    }

    public function testNotSVGFiles()
    {
        $this->SVG->is_debug = false;
        $svg = $this->SVG->embed('wrong.png');
        $this->assertNull($svg);
    }

    public function testNotSVGFilesDebug()
    {
        $this->SVG->is_debug = true;
        $svg = $this->SVG->embed('wrong.png');
        $this->assertNull($svg);
        $this->expectOutputRegex('/SVG Lib Error:/');
    }
}
