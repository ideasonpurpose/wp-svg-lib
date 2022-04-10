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

        // This is never going to work since the second
        // extension is stripped off when normalizing requested keys
        // $svg = $this->SVG->embed('diff.svg.svg');
        // $this->assertStringContainsString('<g fill="pink"', $svg);
    }

    public function testNotSVGFiles()
    {
        $this->SVG->WP_DEBUG = false;
        $svg = $this->SVG->embed('wrong.png');
        $this->assertNull($svg);
    }

    public function testNotSVGFilesDebug()
    {
        $this->SVG->WP_DEBUG = true;
        $svg = $this->SVG->embed('wrong.png');
        $this->assertNull($svg);
        $this->expectOutputRegex('/SVG Lib Error:/');
    }
}
