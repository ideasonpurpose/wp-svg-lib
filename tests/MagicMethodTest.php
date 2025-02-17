<?php namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use IdeasOnPurpose\WP\Test;

Test\Stubs::init();

#[CoversClass(\IdeasOnPurpose\WP\SVG::class)]
final class MagicMethodTest extends TestCase
{
    public $SVG;

    public function setUp(): void
    {
        $this->SVG = new SVG();
    }

    /**
     * Test magic methods for embedding SVGs
     *
     * Exact matches should work when quoted.
     * All names should also be directly callable using camelCase
     */
    public function testMagicGet()
    {
        $arrow = $this->SVG->arrow;
        $this->assertStringContainsString('<svg', $arrow);
    }

    /**
     * Test magic methods for embedding SVGs
     *
     * Exact matches should work when quoted.
     * All names should also be directly callable using camelCase
     */
    public function testMagicCall()
    {
        $widthArg = ['width' => 25];

        /** @var SVG|MockObject $svg */
        $svg = $this->getMockBuilder('\IdeasOnPurpose\WP\SVG')
            ->disableOriginalConstructor()
            ->onlyMethods(['embed'])
            ->getMock();

        $svg->expects($this->once())
            ->method('embed')
            ->with($this->equalTo('arrow'), $this->equalTo($widthArg));

        $svg->arrow($widthArg);
        // $this->assertStringContainsString('<svg', $arrow);
        // $this->assertStringContainsString('width="25"', $arrow);
    }

    public function testMagicCallNoAttributes()
    {
        /** @var SVG|MockObject $svg */
        $svg = $this->getMockBuilder('\IdeasOnPurpose\WP\SVG')
            ->disableOriginalConstructor()
            ->onlyMethods(['embed'])
            ->getMock();

        $svg->expects($this->once())
            ->method('embed')
            ->with($this->equalTo('arrow'), $this->equalTo([]));

        $svg->arrow();
    }
}
