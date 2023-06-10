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

    public function testEmbedSvg2()
    {
        $this->SVG = new SVG();

        $lib = ' {"test": {"content": {"raw": "<svg></svg>"}, "_links": {"self": "http://link"}}}';

        $lib = '{
            "circle": {
                "content": { "raw": "<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" fill="#f00" ><circle cx="50" cy="50" r="50"  /></svg>" },
                "_links": { "self": "https://example.com" }
            },
            "square": {
                "content": { "raw": "<svg viewBox="0 0 100 100"><rect width="100" height="100" /></svg>" },
                "_links": { "self": "https://example.com" }
            }
        }';
        $this->SVG->lib = (array) json_decode($lib, false);
        d($this->SVG->lib);

        $args = ['test', 'height' => '40', 'width' => 'auto'];

        // $actual = $this->SVG->cleanSvg('test', $args);
        // $actual = $this->SVG->normalizeSvg('<svg></svg>', $args);

        $actual = $this->SVG->svgShortcode(['circle', 'height'=> '40', 'width' => '120']);
        d($actual);
        $this->assertEquals($expected, $actual);

    }
}
