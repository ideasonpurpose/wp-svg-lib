<?php namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();
/**


/**
 * @covers \IdeasOnPurpose\WP\SVG
 */
final class ReWrapSvgTest extends TestCase
{
    public $SVG;
    public $lib;

    public function setUp(): void
    {
        $this->SVG = new SVG();
        $this->lib = [
            (object) [
                'svg' => '<svg></svg>',
                'innerContent' => '',
                'width' => 24,
                'height' => 24,
                'aspect' => 1,
                'attributes' => [],
                '_links' => (object) ['self' => 'SELF_URL', 'svg' => 'SVG_URL'],
            ],
        ];
    }

    public function testNoViewbox()
    {
        $w = 40;
        $h = 25;
        $svg = $this->lib[0];
        $svg->width = $w;
        $svg->height = $h;

        // $expected = '<svg viewBox="0 0 40 25"/>';
        $actual = $this->SVG->rewrapSvg($svg);

        $this->assertStringContainsString('viewBox', $actual->svg);
        $this->assertEquals($w, $actual->width);
        $this->assertEquals($h, $actual->height);
    }

    public function testRestoreViewbox()
    {
        $width = 36;
        $height = 48;
        $viewBox = "0 0 {$width} {$height}";

        $svg = $this->lib[0];
        $expected = sprintf(
            '<svg width="%d" height="%d" viewBox="%s" xmlns="http://www.w3.org/2000/svg"></svg>',
            $width,
            $height,
            $viewBox
        );

        $actual = $this->SVG->rewrapSvg($svg, ['width' => $width, 'height' => $height]);
        $this->assertEquals($expected, $actual->svg);
    }

    public function testAutoWidth()
    {
        $x = 2;
        $y = 4;
        $width = 36;
        $height = 48;
        $viewBox = "{$x} {$y} {$width} {$height}";
        $svg = $this->lib[0];
        $svg->width = $width;
        $svg->height = $height;
        $svg->aspect = $width / $height;
        $svg->attributes['viewBox'] = $viewBox;

        $newHeight = 120;
        $autoWidth = ($width / $height) * $newHeight;
        $expected = sprintf(
            '<svg width="%d" height="%d" viewBox="%s" xmlns="http://www.w3.org/2000/svg"></svg>',
            $autoWidth,
            $newHeight,
            $viewBox
        );
        $args = ['width' => 'auto', 'height' => $newHeight];

        $actual = $this->SVG->rewrapSvg($svg, $args);

        $this->assertStringContainsString("height=\"{$newHeight}\"", $actual->svg);
        $this->assertStringContainsString("width=\"{$autoWidth}\"", $actual->svg);
        $this->assertEquals($expected, $actual->svg);
        $this->assertEquals($autoWidth, $actual->attributes['width']);
        $this->assertEquals($newHeight, $actual->attributes['height']);
    }

    public function testAutoHeight()
    {
        $expected = '<svg height="48" viewBox="0 0 36 48" xmlns="http://www.w3.org/2000/svg"></svg>';
        $args = ['height' => 'auto'];

        $svg = $this->lib[0];
        $svg->width = 36;
        $svg->height = 48;
        $svg->aspect = 36 / 48;
        $svg->attributes['viewBox'] = '0 0 36 48';

        $actual = $this->SVG->rewrapSvg($svg, $args);
        $this->assertEquals($expected, $actual->svg);
        $this->assertEquals(48, $actual->height);
    }

    public function testDoubleAuto()
    {
        $args = ['width' => 'auto', 'height' => 'auto'];

        $svg = $this->lib[0];
        $svg->width = 100;
        $svg->height = 50;
        $svg->aspect = 2;
        $svg->attributes['viewBox'] = '10 10 100 50';

        $expected = '<svg width="100" height="50" viewBox="10 10 100 50" xmlns="http://www.w3.org/2000/svg"></svg>';

        $actual = $this->SVG->rewrapSvg($svg, $args);
        $this->assertEquals($expected, $actual->svg);

        $this->assertStringContainsString('viewBox', $actual->svg);
    }

    public function testAddClasses()
    {
        $args = ['class' => 'red green blue'];
        $svg = $this->lib[0];
        $actual = $this->SVG->rewrapSvg($svg, $args);
        $this->assertStringContainsString('red', $actual->svg);
    }

    public function testNoDimenionsOrViewBox()
    {
        $svg = $this->lib[0];
        $svg->attributes = [];
        $svg->width = null;
        $svg->height = null;

        $actual = $this->SVG->rewrapSvg($svg);
        $this->assertStringNotContainsString('viewBox', $actual->svg);
    }

    public function testNoOriginalAttributes(): void
    {
        $args = [];

        $svg = $this->lib[0];
        $svg->attributes['viewBox'] = '0 0 24 24';
        $actual = $this->SVG->rewrapSvg($svg, $args);
        $this->assertObjectNotHasProperty('original_attributes', $actual);
    }

    public function testAddOriginalAttributes(): void
    {
        $args = ['width' => 44];

        $svg = $this->lib[0];
        $svg->attributes['viewBox'] = '0 0 24 24';
        $actual = $this->SVG->rewrapSvg($svg, $args);
        $this->assertObjectHasProperty('original_attributes', $actual);
    }
}
