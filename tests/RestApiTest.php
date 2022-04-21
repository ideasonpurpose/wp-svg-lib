<?php

namespace IdeasOnPurpose\WP;

use PHPUnit\Framework\TestCase;
use IdeasOnPurpose\WP\Test;
use WP_REST_Request;

Test\Stubs::init();
/**


/**
 * @covers \IdeasOnPurpose\WP\SVG
 */
final class RestApiTest extends TestCase
{
    protected function setUp(): void
    {
        $this->SVG = new SVG(__DIR__ . '/fixtures/svg');
        $this->SVG->init();
    }

    /**
     * Check that set_query_var is called when there are any SVGs in the library
     */
    public function testGetAttributeFromRestParams_good(): void
    {
        $classList = 'red green blue';
        $params = ['width' => 'AUTO', 'height' => 123, 'class' => $classList];

        $req = new WP_REST_Request($params);

        $this->SVG->getAttributesFromRestParams($req);

        $this->assertArrayHasKey('width', $this->SVG->attributes);
        $this->assertArrayHasKey('height', $this->SVG->attributes);
        $this->assertArrayHasKey('class', $this->SVG->attributes);
        $this->assertEquals('auto', $this->SVG->attributes['width']);
        $this->assertEquals($classList, $this->SVG->attributes['class']);
    }

    public function testGetAttributeFromRestParams_bad(): void
    {
        $params = ['width' => '2d20', 'height' => -25];

        $req = new WP_REST_Request($params);

        $this->SVG->getAttributesFromRestParams($req);

        $this->assertArrayNotHasKey('width', $this->SVG->attributes);
        $this->assertArrayNotHasKey('height', $this->SVG->attributes);
    }

    /**
     * @runInSeparateProcess
     * Run in separate process to suppress "headers already sent" error
     */
    public function testReturnSvgFile(): void
    {
        $mockSvg = $this->getMockBuilder('\IdeasOnPurpose\WP\SVG')
            ->onlyMethods(['exit'])
            ->getMock();

        $mockSvg->method('exit')->willReturnArgument(0);

        /** @var \IdeasOnPurpose\WP\SVG $mockSvg */
        $mockSvg->WP_DEBUG = false;
        $mockSvg->libDir = __DIR__ . '/fixtures/svg';
        $mockSvg->init();

        $req = new WP_REST_Request(['name' => 'arrow']);
        /** @var String $actual */
        $actual = $mockSvg->returnSvgFile($req);
        $this->assertStringContainsString('<svg xmlns', $actual);

        $req = new WP_REST_Request(['name' => 'arrow', 'raw' => true]);
        /** @var String $actual */
        $actual = $mockSvg->returnSvgFile($req);
        $this->assertStringContainsString('<svg width', $actual);

        /**
         * @link https://stackoverflow.com/a/39892373/503463
         */
        $actual = xdebug_get_headers();
        $this->assertStringContainsString('image/svg+xml', $actual[0]);
    }

    public function testRestResponse(): void
    {
        $req = new WP_REST_Request(['name' => 'arrow']);
        $actual = $this->SVG->restResponse($req);

        $this->assertIsObject($actual);

        $req = new WP_REST_Request([]);
        $actual = $this->SVG->restResponse($req);
        $this->assertIsArray($actual);
    }

    public function testRegisterRestRoutes()
    {
        global $register_rest_route;

        $register_rest_route = [];
        $this->SVG->registerRestRoutes();

        $this->assertCount(3, $register_rest_route);
    }
}
