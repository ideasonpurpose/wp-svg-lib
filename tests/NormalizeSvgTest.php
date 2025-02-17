<?php namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

#[CoversClass(\IdeasOnPurpose\WP\SVG::class)]
final class NormalizeSvgTest extends TestCase
{
    public $SVG;

    public function setUp(): void
    {
        $this->SVG = new SVG();
    }

    public function testBadViewBox()
    {
        $w = 50;
        $h = 40;
        $actual = $this->SVG->normalizeSvg("<svg viewbox='0 1 2' height='$h' width='$w'></svg>");
        $expected = "0 0 $w $h";
        $this->assertEquals($expected, $actual->attributes['viewBox']);
    }

    public function testNoViewBox()
    {
        $w = 50;
        $h = 40;
        $actual = $this->SVG->normalizeSvg("<svg height='$h' width='$w'></svg>");
        $expected = "0 0 $w $h";
        $this->assertEquals($expected, $actual->attributes['viewBox']);
    }

    public function testBadSVG()
    {
        $actual = $this->SVG->normalizeSvg('not xml');
        $this->assertIsObject($actual);
        $this->assertObjectHasProperty('error', $actual);
    }
}
