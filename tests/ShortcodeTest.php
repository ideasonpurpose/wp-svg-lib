<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

#[CoversClass(\IdeasOnPurpose\WP\SVG::class)]
final class ShortcodeTest extends TestCase
{
    public $SVG;

    protected function setUp(): void
    {
        $this->SVG = new SVG(__DIR__ . '/fixtures/svg');
        $this->SVG->init();
    }

    public function testEmbedSvg()
    {
        $actual = $this->SVG->svgShortcode(['arrow']);
        $expected = $this->SVG->lib['arrow']->svg;

        $this->assertStringContainsString('<svg', $actual);
        $this->assertEquals($expected, $actual);
    }

    public function testEmbedScaled()
    {
        /** @var SVG|MockObject $mockSvg */
        $mockSvg = $this->getMockBuilder('\IdeasOnPurpose\WP\SVG')
            ->onlyMethods(['embed'])
            ->getMock();

        $mockSvg->method('embed')->willReturnArgument(1);

        // $this->SVG = new SVG();

        // $lib = '{
        //     "circle": {
        //         "content": { "raw": "<svg viewBox=\"0 0 100 100\" xmlns=\"http://www.w3.org/2000/svg\" fill=\"#f00\" ><circle cx=\"50\" cy=\"50\" r=\"50\" /></svg>" },
        //         "_links": { "self": "https://example.com" }
        //     },
        //     "square": {
        //         "content": { "raw": "<svg viewBox=\"0 0 100 100\"><rect width=\"100\" height=\"100\" /></svg>" },
        //         "_links": { "self": "https://example.com" }
        //     }
        // }';
        // $this->SVG->lib = (array) json_decode($lib, false);

        $args = ['arrow', 'height' => '40', 'width' => 'auto'];
        $expected = ['height' => 40, 'width' => 'auto'];
        $actual = $mockSvg->svgShortcode($args);

        // $expected = $this->SVG->svgShortcode(['circle', 'width' => '40', 'height' => 'auto']);
        $this->assertEquals($expected, $actual);

        // $expected = $this->SVG->svgShortcode(['circle', 'width' => 'auto', 'height' => '40']);
        // $this->assertEquals($expected, $actual);
    }
}
