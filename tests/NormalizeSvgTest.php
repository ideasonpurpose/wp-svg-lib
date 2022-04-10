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
    protected function setUp(): void
    {
        $this->SVG = new SVG();
    }

    public function testNoViewbox()
    {
        $file = '<svg height="25" width="40"></svg>';
        $actual = $this->SVG->normalizeSvg($file);
        $this->assertStringContainsString('viewBox', $actual->content);
    }

    public function testAutoWidth()
    {
        $newHeight = 123;
        $this->SVG->attributes = ['width' => 'auto', 'height' => $newHeight];

        $file = '<svg viewBox="0 0 36 48"></svg>';
        $actual = $this->SVG->normalizeSvg($file);
        $this->assertEquals(36, $actual->width);
        $this->assertStringContainsString("$newHeight", $actual->content);
    }

    public function testAutoHeight()
    {
        $this->SVG->attributes = ['height' => 'auto'];

        $file = '<svg viewBox="0 0 36 48"></svg>';
        $actual = $this->SVG->normalizeSvg($file);
        $this->assertEquals(48, $actual->height);
    }

    public function testDoubleAuto()
    {
        $this->SVG->attributes = ['width' => 'auto', 'height' => 'auto'];

        $file = '<svg viewBox="0 0 36 48"></svg>';
        $actual = $this->SVG->normalizeSvg($file);
        $this->assertStringContainsString('viewBox', $actual->content);
    }

    public function testDimensionsAreIntegers()
    {
        $file = '<svg height="25" width="40"></svg>';
        $actual = $this->SVG->normalizeSvg($file);

        $this->assertIsInt($actual->width);
        $this->assertIsInt($actual->height);
    }

    public function testAddClasses()
    {
        $this->SVG->attributes = ['class' => 'red green blue'];

        $file = '<svg viewBox="0 0 36 48"></svg>';
        $actual = $this->SVG->normalizeSvg($file);
        $this->assertStringContainsString('red', $actual->content);
    }

    public function testNoDimenionsOrViewBox()
    {
        $file = '<svg></svg>';
        $actual = $this->SVG->normalizeSvg($file);
        $this->assertStringNotContainsString('viewBox', $actual->content);
    }
}
