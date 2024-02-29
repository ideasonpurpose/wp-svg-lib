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
    public $SVG;

    protected function setUp(): void
    {
        $this->SVG = new SVG(__DIR__ . '/fixtures/svg');
        $this->SVG->init();
    }

    // public function testHasContent()
    // {
    //     // Check that content contains a raw, clean and inner
    //     $req = new WP_REST_Request(['name' => 'arrow']);
    //     $actual = $this->SVG->restResponse($req);

    //     $this->assertObjectHasProperty('content', $actual);
    //     $this->assertObjectNotHasProperty('raw', $actual->content);
    //     $this->assertObjectHasProperty('svg', $actual->content);
    //     $this->assertObjectHasProperty('inner', $actual->content);
    //     // $this->assertObjectHasProperty('not-a-key', $actual->content);
    // }

    public function testHasLinks()
    {
        // Check that _links contains self, collection, svg and svg_raw
        $req = new WP_REST_Request(['name' => 'arrow']);
        $actual = $this->SVG->restResponse($req);
        $this->assertObjectHasProperty('_links', $actual);
        $this->assertObjectHasProperty('self', $actual->_links);
        $this->assertObjectHasProperty('collection', $actual->_links);
        $this->assertObjectHasProperty('src', $actual->_links);
        $this->assertObjectHasProperty('svg', $actual->_links);
    }

    public function testReturnsCollection()
    {
        $req = new WP_REST_Request([]);
        $actual = $this->SVG->restResponse($req);
        $this->assertIsObject($actual);
        $this->assertObjectHasProperty('arrow', $actual);
    }

    public function testReturnsSVGFile(): void
    {
        $mockSvg = $this->getMockBuilder(\IdeasOnPurpose\WP\SVG::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['returnSvgFile'])
            ->getMock();

        $mockSvg->expects($this->exactly(2))->method('returnSvgFile')->willReturnArgument(0);

        /**
         * $args['svg'] is null
         */
        $name = 'arrow';
        $args = ['name' => $name];
        $req = new WP_REST_Request($args);
        $actual = $mockSvg->restResponse($req);
        $this->assertIsObject($actual);

        /**
         * $args['svg'] is the string "0" (zero)
         * NOTE: all WP_REST_Request params are strings because they're $_GET vars
         */
        $args['svg'] = '0';
        $req = new WP_REST_Request($args);
        $actual = $mockSvg->restResponse($req);
        $this->assertIsObject($actual);

        /**
         * $args['svg'] is a string
         */
        $args['svg'] = 'false';
        $req = new WP_REST_Request($args);
        $actual = $mockSvg->restResponse($req);
        $this->assertEquals($actual, $name);

        /**
         * $args['svg'] is numeric string
         */
        $args['svg'] = '1';
        $req = new WP_REST_Request($args);
        $actual = $mockSvg->restResponse($req);
        $this->assertEquals($actual, $name);
    }

    public function testSelfHasQuery(): void
    {
        $width = 123;
        $req = new WP_REST_Request(['name' => 'arrow', 'width' => $width]);
        $widthQuery = "width={$width}";

        $actual = $this->SVG->restResponse($req);

        $this->assertObjectHasProperty('_links', $actual);
        $this->assertObjectHasProperty('self', $actual->_links);
        $this->assertStringContainsString($widthQuery, $actual->_links->self);
    }

    public function testValidateAttributes_bad(): void
    {
        $attributes = ['width' => '2d20', 'height' => -25];

        $actual = $this->SVG->validateAttributes($attributes);

        $this->assertArrayNotHasKey('width', $actual);
        $this->assertArrayNotHasKey('height', $actual);
    }

    /**
     * @runInSeparateProcess
     * Run in separate process to suppress "headers already sent" error
     */
    public function testReturnSvgFile(): void
    {
        $mockSvg = $this->getMockBuilder(\IdeasOnPurpose\WP\SVG::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['exit'])
            ->getMock();

        $mockSvg->expects($this->once())->method('exit')->willReturnArgument(0);

        // $req = new WP_REST_Request(['name' => 'arrow']);
        $mockSvg->returnSvgFile('arrow');

        /**
         * xdebug_get_headers
         * @link https://stackoverflow.com/a/39892373/503463
         */
        $headers = xdebug_get_headers();
        $this->assertStringContainsString('image/svg+xml', $headers[0]);
    }

    public function testRestResponse(): void
    {
        $req = new WP_REST_Request(['name' => 'arrow']);
        $actual = $this->SVG->restResponse($req);

        $this->assertIsObject($actual);

        $req = new WP_REST_Request([]);
        $actual = $this->SVG->restResponse($req);
        $this->assertIsObject($actual);
    }

    public function testRegisterRestRoutes()
    {
        global $register_rest_route;

        $register_rest_route = [];
        $this->SVG->registerRestRoutes();

        $this->assertCount(2, $register_rest_route);
    }

    /**
     * The response should contain an attributes array with xmlns and viewbox, plus optionally class, height and width
     */
    public function testResponseHasAttributes(): void
    {
        $req = new WP_REST_Request(['name' => 'arrow']);
        $actual = $this->SVG->restResponse($req);
        $this->assertObjectHasProperty('attributes', $actual);
        $this->assertArrayHasKey('viewBox', $actual->attributes);
    }

    /**
     * Responses should include srcPath when WP_DEBUG is true
     */
    public function testResponseHas_srcPath(): void
    {
        $req = new WP_REST_Request(['name' => 'arrow']);
        $this->SVG->WP_DEBUG = true;
        $actual = $this->SVG->restResponse($req);
        $this->assertObjectHasProperty('__srcPath', $actual);
    }

    /**
     * Responses should include srcPath when WP_DEBUG is true
     */
    public function testResponseHasNo_srcPath(): void
    {
        $req = new WP_REST_Request(['name' => 'arrow']);
        $this->SVG->WP_DEBUG = false;
        $actual = $this->SVG->restResponse($req);
        $this->assertObjectNotHasProperty('__srcPath', $actual);
    }

    public function testNameMismatchThrowsError(): void
    {
        $req = new WP_REST_Request(['name' => 'missing-name']);
        $actual = $this->SVG->restResponse($req);

        $this->assertObjectHasProperty('data', $actual);
        $this->assertArrayHasKey('status', $actual->data);
    }
}
