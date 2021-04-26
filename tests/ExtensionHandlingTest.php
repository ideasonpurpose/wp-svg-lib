<?php

namespace IdeasOnPurpose;

use PHPUnit\Framework\TestCase;

/**
 * @covers \IdeasOnPurpose\SVG
 */
final class ExtensionHandlingTest extends TestCase
{
    protected function setUp(): void
    {
        $this->SVG = new SVG(__DIR__ . '/fixtures/svg/extensions');
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
        $this->assertStringContainsString('<svg fill="pink"', $svg);
    }
}
