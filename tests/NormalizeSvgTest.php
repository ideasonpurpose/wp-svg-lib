<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;

Test\Stubs::init();
/**


/**
 * @covers \IdeasOnPurpose\WP\SVG
 */
final class NormalizeSvgTest extends TestCase
{
    public $SVG;

    public function setUp(): void
    {
        $this->SVG = new SVG();
    }

    public function testNoViewbox()
    {
        $file = '<svg height="25" width="40"></svg>';
        $expected = '<svg viewBox="0 0 40 25"/>';
        $actual = $this->SVG->normalizeSvg($file);

        $this->assertEquals($expected, $actual->content);
        $this->assertEquals(40, $actual->width);
        $this->assertEquals(25, $actual->height);
    }

    public function testAutoWidth()
    {
        $x = 2;
        $y = 4;
        $width = 36;
        $height = 48;
        $viewBox = "{$x} {$y} {$width} {$height}";
        $file = sprintf('<svg viewBox="%s"></svg>', $viewBox);

        $newHeight = 120;
        $autoWidth = ($width / $height) * $newHeight;
        $expected = sprintf('<svg viewBox="%s" width="%d" height="%d"/>', $viewBox, $autoWidth, $newHeight);
        $args = ['width' => 'auto', 'height' => $newHeight];

        $actual = $this->SVG->normalizeSvg($file, $args);

        $this->assertStringContainsString("height=\"{$newHeight}\"", $actual->content);
        $this->assertStringContainsString("width=\"{$autoWidth}\"", $actual->content);
        $this->assertEquals($expected, $actual->content);
        $this->assertEquals($autoWidth, $actual->width);
        $this->assertEquals($newHeight, $actual->height);
    }

    public function testAutoHeight()
    {
        $file = '<svg viewBox="0 0 36 48"></svg>';
        $expected = '<svg viewBox="0 0 36 48" height="48"/>';
        $args = ['height' => 'auto'];

        $actual = $this->SVG->normalizeSvg($file, $args);
        $this->assertEquals($expected, $actual->content);
        $this->assertEquals(48, $actual->height);
    }

    public function testDoubleAuto()
    {
        $args = ['width' => 'auto', 'height' => 'auto'];

        $file = '<svg viewBox="10 10 100 50"></svg>';
        $expected = '<svg viewBox="10 10 100 50" width="100" height="50"/>';

        $actual = $this->SVG->normalizeSvg($file, $args);
        $this->assertEquals($expected, $actual->content);

        $this->assertStringContainsString('viewBox', $actual->content);
    }

    public function testDimensionsAreIntegers()
    {
        $width = 40;
        $height  = 25;
        $file = sprintf('<svg width="%d" height="%d"></svg>', $width, $height);
        $actual = $this->SVG->normalizeSvg($file);

        $this->assertIsInt($actual->width);
        $this->assertEquals($width, $actual->width);

        $this->assertIsInt($actual->height);
        $this->assertEquals($height, $actual->height);
    }

    public function testAddClasses()
    {
        $args = ['class' => 'red green blue'];

        $file = '<svg viewBox="0 0 36 48"></svg>';
        $actual = $this->SVG->normalizeSvg($file, $args);
        $this->assertStringContainsString('red', $actual->content);
    }

    public function testNoDimenionsOrViewBox()
    {
        $file = '<svg></svg>';
        $actual = $this->SVG->normalizeSvg($file);
        $this->assertStringNotContainsString('viewBox', $actual->content);
    }
}
