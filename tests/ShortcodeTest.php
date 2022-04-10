<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();
/**


/**
 * @covers \IdeasOnPurpose\WP\SVG\Shortcodes
 */
final class ShortcodeTest extends TestCase
{
    public function testEmbedSvg()
    {
        $Shortcodes = new SVG\Shortcodes();
        $actual = $Shortcodes->embedSvg(['a' => 12, 'b' => 'abc'], 'text');

        $this->assertEquals('hello', $actual);
    }
}
