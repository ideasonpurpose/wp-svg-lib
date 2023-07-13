<?php
namespace IdeasOnPurpose\WP;

use Doctrine\Inflector\InflectorFactory;

/**
 * A library for embedding SVGs from the filesystem. Values are returned
 * as strings and must be printed.
 *
 * SVGs can be directly embedded through magic methods like these:
 *      $SVG->name;
 *      $SVG->arrow;
 *
 * SVGs with incompatible names can be embedded like this:
 *      $SVG->embed('kebab-case-name');
 *
 * All discovered files are normalized to camelCase, so this would also work:
 *      $SVG->kebabCaseName;
 *
 * SVGs can be inserted as linked symbols with `$SVG->use('name')`
 * A deprecated legacy method `$SVG->get('name')` is an alias of `use`
 */
class SVG
{
    public $lib = [];

    /**
     * A placeholder for WP_DEBUG which can be mocked
     */
    public $is_debug = false;
    /**
     * A placeholder for the ABSPATH constant which can be mocked
     */
    public $abspath;

    public $libDir;
    public $transient;
    public $rest_namespace;
    public $rest_route;
    public $rest_base;
    public $inUse;
    public $shortcode;

    public function __construct($libDir = null)
    {
        $this->is_debug = defined('WP_DEBUG') && WP_DEBUG;
        $this->abspath = defined('ABSPATH') ? ABSPATH : false;

        $this->libDir = $libDir ?? get_template_directory() . '/dist/images/svg';

        //TODO: Can we store the theme version in here to force a refresh when themes are updated?
        $this->transient = get_class($this) . ':' . $this->libDir;

        $this->rest_namespace = 'ideasonpurpose/v1';
        $this->rest_route = 'svg';
        $this->rest_base = "{$this->rest_namespace}/{$this->rest_route}";

        $this->inUse = [];

        $this->shortcode = 'svg';

        add_action('pre_get_posts', [$this, 'registerQueryVar']);
        add_action('wp_footer', [$this, 'dumpSymbols']);

        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        add_action('wp_loaded', [$this, 'init']);

        add_action('init', [$this, 'addShortcode']);
    }

    /**
     * Isolate calls to `exit` so we can run PHPUnit without exiting
     * All this does is die.
     *
     * @codeCoverageIgnore
     */
    public function exit($content)
    {
        exit($content);
    }

    /**
     * Initialization is stored in a transient since this stuff rarely changes and
     * there's no need to burn CPU cycles to re-generate this on every request.
     *
     * The library stored in a transient to reduce server load.
     */
    public function init()
    {
        $startTime = microtime(true);
        $this->lib = get_transient($this->transient);

        /**
         * Disable transients when WP_DEBUG is true
         */
        if ($this->is_debug === true) {
            $this->lib = false;
        }

        if ($this->lib === false) {
            $this->lib = [];
            $this->loadFromDirectory($this->libDir);
            $this->libfill();
            $this->lib['_from_transient'] = false;

            set_transient($this->transient, $this->lib, 12 * HOUR_IN_SECONDS);
        } else {
            // Note: This is safe because normalized keys will never start with an underscore
            $this->lib['_from_transient'] = true;
        }
        $this->lib['_processing_time'] = sprintf('%04fs', microtime(true) - $startTime);
    }

    public function registerQueryVar()
    {
        if (count($this->lib) > 0) {
            set_query_var('SVG', $this);
        }
    }

    /**
     * Checks $dir for SVG files and includes any found using the files' baseName as the storage key.
     *
     * The raw file contents are stored initially, contents are validated and cleaned upon request.
     *
     * NOTE: This uses $file->getPathname instead of basename to accommodate searching subdirectories
     */
    public function loadFromDirectory($dir)
    {
        if (!$dir || !file_exists($dir) || !is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveDirectoryIterator($dir);
        foreach (new \RecursiveIteratorIterator($iterator) as $file) {
            if (strtolower($file->getExtension()) === 'svg') {
                $key = str_replace($dir, '', $file->getPathname());
                $key = ltrim($key, '/');

                $key = $this->normalizeKey($key);
                $restSelf = get_rest_url(null, "{$this->rest_base}/{$key}");

                $svg = $this->normalizeSvg(file_get_contents($file->getRealPath()));

                $rootRelPath = str_replace($this->abspath, '', $file->getRealPath());
                $srcUrl = site_url($rootRelPath);

                /**
                 * NOTE: Keys prefixed with double-underscores are private and will be scrubbed
                 * from non-debug output
                 */
                $svg->__srcPath = $file->getRealPath();
                $svg->_links = (object) [
                    'self' => $restSelf, // url pointing to a JSON representation including any query vars
                    'collection' => get_rest_url(null, "{$this->rest_base}"), // Collection of all SVGs, query vars ignored
                    'svg' => $restSelf . '.svg',
                    'src' => $srcUrl, // direct url to the source file
                ];

                $this->lib[$key] = $svg;
            }
        }
        ksort($this->lib);
    }

    /**
     * This re-constructs and re-wraps a normalized SVG object and modifies attributes
     * based on the provided args. This can be used to inject width/height attributes, classes or an ID.
     *
     * $args are generally passed from getAttributesFromRestParams(), where they've already been
     * validated.
     */
    public function rewrapSvg($svg, $attributes = [])
    {
        $esc_atts = array_map('urlencode', $attributes);
        $svg->_links->self = add_query_arg($esc_atts, $svg->_links->self);
        $svg->_links->svg = add_query_arg($esc_atts, $svg->_links->svg);

        $aspect = $svg->aspect;
        $width = $svg->width;
        $height = $svg->height;
        $viewBox = explode(' ', $svg->attributes['viewBox']);

        $newWidth = array_key_exists('width', $attributes) ? $attributes['width'] : null;
        $newHeight = array_key_exists('height', $attributes) ? $attributes['height'] : null;

        if (count($viewBox) == 4) {
            $width = $width ?: intval($viewBox[2]);
            $height = $height ?: intval($viewBox[3]);
        }

        if ($newWidth == 'auto' && $newHeight == 'auto') {
            $newWidth = $width;
            $newHeight = $height;
        }

        if ($newWidth) {
            if ($newWidth == 'auto') {
                $h = $newHeight ?: $height;
                $newWidth = round($h * $aspect);
            }
            $width = $newWidth;
            $attributes['width'] = $newWidth;
        }

        if ($newHeight) {
            if ($newHeight == 'auto') {
                $w = $newWidth ?: $width;
                $newHeight = round($w / $aspect);
            }
            $height = $newHeight;
            $attributes['height'] = $newHeight;
        }

        /**
         * Restore viewBox width/height
         */
        if (count($viewBox) != 4 && $width && $height) {
            $viewBox = [0, 0, $width, $height];
        }

        if (count($viewBox) === 4) {
            $attributes['viewBox'] = implode(' ', $viewBox);
        }

        $svg->old_attributes = $svg->attributes;
        $svg->attributes = $attributes;
        $svg->svg = $this->wrapSvg($svg->innerContent, $attributes);

        return $svg;
    }

    /**
     * Validates and normalizes SVGs. Returns an object
     *
     * @param  string $rawSVGString - A blob of SVG content
     * @return object {
     * TODO: UPDATE THIS
     *              'svg' => String,
     *              'innerContent' => String,
     *              'width' => 'Integer',
     *              'height' => Integer,
     *              'aspect' => Float,
     *              'attributes' => Object
     *              }
     */
    public function normalizeSvg($rawSVGString, $args = [])
    {
        // $svg = null;
        // $aspect = 1;

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string(trim($rawSVGString));

        /**
         * If we can't parse the SVG, bail early
         */
        if ($xml === false) {
            return (object) ['error' => libxml_get_errors()];
        }

        /**
         * NOTE: SimpleXMLElements attributes method returns objects, these
         * need to be coerced to strings, otherwise the variable assignment
         * breaks when the attributes are unset
         */
        $xml_attributes = [];
        foreach ($xml->attributes() as $k => $v) {
            $xml_attributes[strtolower((string) $k)] = (string) $v;
        }

        $width = array_key_exists('width', $xml_attributes) ? $xml_attributes['width'] : null;
        $height = array_key_exists('height', $xml_attributes) ? $xml_attributes['height'] : null;
        $viewBox = array_key_exists('viewbox', $xml_attributes) ? explode(' ', $xml_attributes['viewbox']) : [];

        $attributes = [];

        /**
         * Remember ViewBox is min-x, min-y, WIDTH and HEIGHT, the first two numbers are NOT x,y dimensions
         */
        if (count($viewBox) == 4) {
            $width = $width ?: $viewBox[2];
            $height = $height ?: $viewBox[3];
        }

        $aspect = $width && $height ? $width / $height : 1;

        /**
         * Restore viewBox width/height
         */
        if (count($viewBox) != 4 && $width && $height) {
            $viewBox = [0, 0, $width, $height];
        }

        if (count($viewBox) === 4) {
            $attributes['viewBox'] = implode(' ', $viewBox);
        }

        $attributes = array_filter($attributes, 'strlen');

        /**
         * Remove the XML Declaration then strip <svg> container
         */
        $svg = preg_replace('/^<\?xml[^<]+/mi', '', $xml->asXML());
        $contents = preg_replace('%</?svg[^>]*>\s*%', '', $svg);

        $output = [
            'svg' => $this->wrapSvg($contents, $attributes),
            'innerContent' => $contents,
            'width' => intval($width),
            'height' => intval($height),
            'aspect' => $aspect,
            'attributes' => $attributes,
        ];

        return (object) $output;
    }

    /**
     *
     * TODO: This could roll up into rewrapSvg, just pass a simple opening tag if there are no args?
     *
     * Wraps $contents with an SVG container. The opening tag is constructed from a restricted
     * list of key=>value $attributes. Attribute order is enforced:
     *     id, class, width, height, viewBox, xmlns
     * CamelCasing of viewBox is NOT enforced, if case doesn't match, it will be omitted.
     * @param string $contents
     * @param array $attributes
     * @return string
     */
    public function wrapSvg($contents, $attributes)
    {
        $atts = array_fill_keys(['id', 'class', 'width', 'height', 'viewBox'], '');
        $atts = array_replace($atts, array_intersect_key($attributes, $atts));
        $atts = array_filter($atts, 'strlen');

        $tag = '<svg';
        foreach ($atts as $label => $value) {
            $tag = sprintf('%s %s="%s"', $tag, $label, $value);
        }
        $tag .= ' xmlns="http://www.w3.org/2000/svg">';
        return $tag . $contents . '</svg>';
    }

    /**
     * Returns a REST-safe key name. Can be round-tripped and will always return the correct
     * key, even after directory correction. eg. 'social/icon.svg' and 'social__icon' will
     * return 'social__icon'
     *
     * Normalize keys to camelCase then replace path-separators with double-underscores
     * If the key does not already exist in $this->lib, link the new key to the original
     *
     * Preserves double-underscore directory separators
     * @return string Always returns a string, either modified $key or ""
     */
    public function normalizeKey($key)
    {
        if (!$key) {
            return '';
        }
        $inflector = InflectorFactory::create()->build();

        $newKey = preg_replace('/\.svg$/i', '', $key);
        $newKey = preg_replace('/\//', '__', $newKey);
        $keyParts = explode('__', $newKey);
        $keyParts = array_map([$inflector, 'camelize'], $keyParts);
        $newKey = implode('__', $keyParts);
        return $newKey;
    }

    /**
     * Check if SVG exists and prints debugging messages if missing
     */
    private function hasSVG($name)
    {
        $key = $this->normalizeKey($name);

        if (!array_key_exists($key, $this->lib) || !property_exists($this->lib[$key], 'svg')) {
            if ($this->is_debug) {
                $error = "SVG Lib Error: The key '$key' does not match any registered SVGs";
                error_log($error);
                echo "\n<!-- $error -->\n\n";
            }
            return false;
        }
        return true;
    }

    public function __get($name)
    {
        return $this->embed($name);
    }

    /**
     * Inline SVGs directly by name
     * '.svg' extensions are stripped, so 'arrow' and 'arrow.svg' will both return the 'arrow.svg' file
     *
     * NOTE: The magic __get method can only accept a single argument, so embed must be
     * called directly if args are being used.
     *
     * // TODO: Fix this type def
     * @param $args {[width?: [Number|'auto'], height?: <Number|'auto'>, class?: String}]
     *
     */
    public function embed($key, $args = [])
    {
        $name = $this->normalizeKey($key);

        if ($this->hasSVG($name)) {
            // TODO: Is it possible for cleanSVG to return null?

            return $this->rewrapSvg($name, $args); //?? $this->lib[$name]->content->raw;
        }
    }

    /**
     * include SVGs as linked symbols
     * This replaces the legacy SVG::get method
     *
     * '.svg' extensions are stripped, so 'arrow' and 'arrow.svg' will both return the 'arrow.svg' file
     */
    public function use($key)
    {
        $name = $this->normalizeKey($key);

        if ($this->hasSVG($name)) {
            array_push($this->inUse, $name);
            return sprintf('<svg class="%1$s"><use xlink:href="#%1$s" href="#%1$s" /></svg>', $name);
        }
    }

    /**
     * Deprecated legacy method to include SVGs as linked symbols. Aliased to SVG::use
     * Calls to SVG::get will print a warning, rename these to SVG::use or switch to
     * direct embeds.
     * @deprecated
     */
    public function get($name)
    {
        echo "\n\n<!-- The get method is deprecated. Switch to `use` instead. --> ";
        return $this->use($name);
    }

    /**
     * Alias for `debug`
     * @deprecated
     */
    public function directory()
    {
        echo "\n\n<!-- The directory method is deprecated --> ";
        $this->debug();
    }

    /**
     * prints a table of registered SVGs
     */
    public function debug()
    {
        if (count($this->lib) > 0) {
            $id = 'debug-' . md5(microtime(true));
            echo "<style>
            #$id {
                display: grid;
                grid-template-columns: repeat(2, 36px auto);
                align-items: center;
                justify-content: center;
                margin: 1rem auto;
                padding: 1rem;

                color: #c00;
                font-size: 14px;
                font-family: monospace;
                white-space: nowrap;
                column-gap: 6px;

                background: #eee;

                row-gap: 6px;
            }
            #$id svg {
                color: magenta;
                width: 36px;
                height: 36px;
                background: #ccc;
                fill: #555;
            }
            </style>";

            echo '<div id="' . $id . '" >';
            foreach ($this->lib as $key => $svg) {
                printf('<div>%s</div>', $this->use($key));
                echo "<div style='color: #bbb'>\$SVG->get(\"<span style='color:#c00'>$key</span>\")</div>";
            }
            echo '</div>';
        }
        return $this->lib;
    }

    /**
     * Copies static variables into $this->lib
     * This is largely for compatibility since we've previously been echoing static variables as needed
     * In the future, this can be refactored away and SVG content can be directly entered into $this->lib
     *
     * Note: `get_class_vars(get_called_class())` pulls static vars in from the child class
     *
     * @deprecated
     */
    private function libFill()
    {
        $has_static_vars = false;
        $static_vars = get_class_vars(get_called_class());
        foreach ($static_vars as $key => $svg) {
            if (is_string($svg) && substr($svg, 0, 4) == '<svg') {
                $newKey = $this->normalizeKey($key);

                /**
                 * This is copied directly from $this-loadFromDirectory
                 * Loading Static SVGs from a child class is deprecated,
                 * so all of this will eventually go away
                 */
                $restSelf = get_rest_url(null, "{$this->rest_base}/{$key}");

                $this->lib[$newKey] = (object) [
                    'content' => (object) ['raw' => $svg],
                    '_links' => (object) [
                        'self' => $restSelf,
                        'collection' => get_rest_url(null, "{$this->rest_base}"),
                        'raw' => add_query_arg(['raw' => ''], $restSelf . '.svg'),
                    ],
                ];
                $has_static_vars = true;
            }
        }
        // Only sort if static vars have been added
        if ($has_static_vars) {
            ksort($this->lib);
            echo "\n\n<!-- Loading SVGs from static child classes is deprecated. Load from a directory instead. -->\n";
        }
    }

    /**
     * Prints an SVG containing all the symbols referenced in the document.
     *
     * @deprecated
     */
    public function dumpSymbols()
    {
        echo "\n\n<!-- SVG:dumpSymbols is deprecated. View the full list of SVGs from the Rest API. -->\n";

        if (count($this->inUse)) {
            $this->inUse = array_unique($this->inUse);
            sort($this->inUse);
            $symbols = array_map(function ($key) {
                return preg_replace(
                    ['%<svg .*(viewbox="[^"]*")[^>]*>(.*)%i', '%</svg>%'],
                    ["    <symbol id=\"$key\" $1>$2", '</symbol>'],
                    $this->lib[$key]->svg
                );
            }, $this->inUse);
            $symbols = implode("\n", $symbols);
            printf("<svg xmlns='http://www.w3.org/2000/svg' style='display: none;'>\n%s\n</svg>\n", $symbols);
        } else {
            if (is_user_logged_in()) {
                echo "<!-- NO SVGs IN USE -->\n";

                if ($this->is_debug) {
                    $trace = array_map(fn($i) => $i['file'] . ':' . $i['line'], debug_backtrace());
                    $trace = implode("\n\t", $trace);
                    printf("<!-- SVG::dumpSymbols call stack:\n\t%s\n -->\n", $trace);
                }
            }
        }
    }

    /**
     * Register REST routes to return SVG listings and individual files
     *
     * Note that rest_route declaration order matters. Rules with the most specificity should appear first
     */
    public function registerRestRoutes()
    {
        // d($this->rest_base);

        register_rest_route($this->rest_namespace, "/{$this->rest_route}/(?P<name>[^/]*)\.svg", [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'returnSvgFile'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($this->rest_namespace, "/{$this->rest_route}/(?P<name>[^/]*)", [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'restResponse'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($this->rest_namespace, "/{$this->rest_route}", [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'restResponse'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function returnSvgFile(\WP_REST_Request $req)
    {
        $name = $req->get_param('name');
        if ($this->hasSVG($name)) {
            $atts = $this->getAttributesFromRestParams($req);
            $svg = $this->lib[$name];
            $svg = $this->rewrapSvg($svg, $atts);

            // NOTE: Disable header to debug SVG contents in the browser
            // header('Content-type: image/svg+xml');
            return $this->exit($svg->svg);
        }
    }

    /**
     * Check for $this->is_debug and remove private underscore-prefixed keys when false
     * @param object $res
     * @return object
     */
    public function removePrivateKeys($res)
    {
        // $this->is_debug = false; // debug toggle
        if (!is_iterable($res) || $this->is_debug) {
            return $res;
        }
        $clean = (object) [];

        foreach ($res as $key => $value) {
            if (substr($key, 0, 2) == '__') {
                continue;
            }
            $clean->$key = $value;
        }

        return $clean;
    }

    public function restResponse(\WP_REST_Request $req)
    {
        $name = $this->normalizeKey($req->get_param('name'));

        if ($name && $this->hasSVG($name)) {
            /**
             * Only apply attributes to requests for a single SVG
             */
            $atts = $this->getAttributesFromRestParams($req);
            $svg = $this->lib[$name];
            $svg = $this->removePrivateKeys($svg);
            $svg->name = $name;
            $svg = $this->rewrapSvg($svg, $atts);
            return rest_ensure_response($svg);
        }

        $lib = (object) [];
        foreach ($this->lib as $name => $svg) {
            $lib->$name = $this->removePrivateKeys($svg);
        }
        return rest_ensure_response($lib);
    }

    /**
     * Extract and validate known attributes from REST Request Params
     * Supported parameters: id, class, width, height
     */
    public function getAttributesFromRestParams(\WP_REST_Request $req)
    {
        $atts = [];
        $atts['id'] = (string) $req->get_param('id');
        $atts['class'] = (string) $req->get_param('class');

        /**
         * Only store height/width if they are 'auto' or positive integers
         */
        $pattern = '/^(?:auto|[0-9]+)$/i';
        $width = (string) $req->get_param('width');
        $height = (string) $req->get_param('height');
        $width = preg_match($pattern, $width) ? strtolower($width) : '';
        $height = preg_match($pattern, $height) ? strtolower($height) : '';
        $atts['width'] = $width === 'auto' ? $width : intval($width);
        $atts['height'] = $height === 'auto' ? $height : intval($height);

        return array_filter($atts, 'strlen');
    }

    /**
     * Register Shortcode
     * @codeCoverageIgnore
     */
    public function addShortcode()
    {
        if (!shortcode_exists($this->shortcode)) {
            add_shortcode($this->shortcode, [$this, 'svgShortcode']);
        }
    }

    /**
     * Embed SVG shortcode
     *
     * This is basically just a wrapper for SVG::embed
     *
     * Example 1: [svg file-slug]
     *
     * Example 2: [svg file-slug height="23" width="auto"]
     *
     * Example 3: [svg src="fileSlug" height="23" width="auto" class="hello there"]
     *
     * TODO:  Add test for bad attributes
     *          [svg src="45" ]
     *          [svg file-slug dog="Stella" class=""]
     *          [svg file-slug width="big" height=3.142856]
     *
     */
    public function svgShortcode(array $atts, ?string $content = '')
    {
        $src = $atts['src'] ?? ($atts[0] ?? null);
        if ($src) {
            unset($atts[0]);
            unset($atts['src']);
            return $this->embed($src, $atts);
        }
    }
}
