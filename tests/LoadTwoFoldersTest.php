<?php

namespace IdeasOnPurpose;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

/**
 * @covers \IdeasOnPurpose\SVG
 */
final class LoadTwoFoldersTest extends TestCase
{
    public function testLoadTwoFolders()
    {
        $this->SVG = new SVG(__DIR__ . '/fixtures/svg');
        $this->SVG->loadFromDirectory(__DIR__ . '/fixtures/svg2');

        $svg = $this->SVG->second;
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->arrow;
        $this->assertStringContainsString('<svg', $svg);
    }
}
