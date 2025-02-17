<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

#[CoversClass(\IdeasOnPurpose\WP\SVG::class)]
final class LoadFromDirectoryTest extends TestCase
{
    public $SVG;

    public function testLoadTwoFolders()
    {
        $this->SVG = new SVG(__DIR__ . '/fixtures/svg');
        $this->SVG->init();
        $this->SVG->loadFromDirectory(__DIR__ . '/fixtures/svg2');

        $this->assertNotEmpty($this->SVG->lib);

        $svg = $this->SVG->second;
        $this->assertStringContainsString('<svg', $svg);

        $svg = $this->SVG->arrow;
        $this->assertStringContainsString('<svg', $svg);
    }

    public function testLoadFromDirectory_no_directory()
    {
        $this->SVG = new SVG();
        $this->SVG->loadFromDirectory('not-a-directory');
        $this->assertEmpty($this->SVG->lib);
    }
}
