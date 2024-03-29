<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

/**
 * @covers \IdeasOnPurpose\WP\SVG
 */
final class LoadFromDirectoryTest extends TestCase
{
    public $SVG;

    public function testLoadTwoFolders()
    {
        $this->SVG = new SVG(__DIR__ . '/fixtures/svg');
        $this->SVG->init();
        $this->SVG->loadFromDirectory(__DIR__ . '/fixtures/svg2');

        $svg = $this->SVG->second;
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->arrow;
        $this->assertStringContainsString('<svg', $svg);
    }

    public function testLoadFromDirectory_no_directory()
    {
        $this->SVG = new SVG();
        $actual = $this->SVG->loadFromDirectory('not-a-directory');
        $this->assertNull($actual);
    }
}
