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
     * Convert WP_DEBUG constant to a property for use in tests
     * @var bool
     */
    public $WP_DEBUG;

    public $libDir;
    public $transient;
    public $rest_namespace;
    public $rest_route;
    public $rest_base;
    public $attributes;
    public $inUse;
    public $shortcode;

    public function __construct($libDir = null)
    {
        $this->WP_DEBUG = defined('WP_DEBUG') && WP_DEBUG;

        $this->libDir = $libDir ?? get_template_directory() . '/dist/images/svg';
        $this->transient = get_class($this) . ':' . $this->libDir;

        $this->rest_namespace = 'ideasonpurpose/v1';
        $this->rest_route = 'svg';
        $this->rest_base = "{$this->rest_namespace}/{$this->rest_route}";

        $this->attributes = [];
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
     */
    public function init()
    {
        $startTime = microtime(true);
        $this->lib = get_transient($this->transient);

        /**
         * Disable transients when WP_DEBUG is true
         */
        if ($this->WP_DEBUG === true) {
            $this->lib = false;
        }

        if ($this->lib === false) {
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
                $this->lib[$key] = (object) [
                    'content' => (object) ['raw' => trim(file_get_contents($file->getRealPath()))],
                    // TODO: maybe use get_theme_root instead of get_template_directory?
                    // @link  https://developer.wordpress.org/reference/functions/get_theme_root/
                    // @link  https://developer.wordpress.org/reference/functions/get_template_directory/
                    'src' => str_replace(get_template_directory() . '/', '', $file->getRealPath()),
                    '_links' => (object) [
                        'self' => $restSelf,
                        'collection' => get_rest_url(null, "{$this->rest_base}"),
                        'raw' => add_query_arg(['raw' => ''], $restSelf . '.svg'),
                    ],
                ];
                $this->cleanSvg($key);
            }
        }
        ksort($this->lib);
    }

    /**
     * TODO: Needs a better name, this is sort of a wrapper/processor for normalizeSvg
     *
     * The main reason for this is so we can re-normalize SVGs with new sizes on demand.
     * This uses names of existing SVG files, normalizeSvg takes a string of SVG content
     *
     * Properties of the Object returned by NormalizeSvg are added to the content in lib
     *
     * @return String As a convenience, return the normalized "clean" SVG content if available
     *                Otherwise an empty string
     *
     */
    public function cleanSvg($name, $args = [])
    {
        if (!$this->hasSVG($name)) {
            return;
        }

        $svg = $this->lib[$name];
        $cleanSvg = $this->normalizeSvg($this->lib[$name]->content->raw, $args);

        if (property_exists($cleanSvg, 'content')) {
            $svg->content->clean = $cleanSvg->content;
            $esc_args = array_map('urlencode', $args);

            $svg->_links->clean = add_query_arg($esc_args, get_rest_url(null, "{$this->rest_base}/{$name}.svg"));
            if (!empty($esc_args)) {
                // TODO: What is this? Why only add clean_json if args are not empty
                $svg->_links->clean_json = add_query_arg($esc_args, $svg->_links->self);
            }
        }

        if (property_exists($cleanSvg, 'width')) {
            $svg->width = $cleanSvg->width;
        }

        if (property_exists($cleanSvg, 'height')) {
            $svg->height = $cleanSvg->height;
        }

        if (property_exists($cleanSvg, 'aspect')) {
            $svg->aspect = $cleanSvg->aspect;
        }

        if (property_exists($cleanSvg, 'error')) {
            $msg = "Error processing {$svg->src}";
            error_log($msg);
            $svg->errors = ['error' => $msg, 'libxml' => $cleanSvg->error];
        }
        return $cleanSvg->content ?? '';
    }

    /**
     *
     * Returns an object containing the following:
     */

    /**
     * normalizeSvg
     *
     * @param  string $rawSVGString - A blob of SVG content
     * @return object {'height' => Integer, 'width' => Integer, 'aspect' => Float, 'content' => String}
     */
    public function normalizeSvg($rawSVGString, $args = [])
    {
        $clean = null;
        $aspect = 1;
        $width = null;
        $height = null;
        $viewBox = [];

        $newWidth = array_key_exists('width', $args) ? $args['width'] : null;
        $newHeight = array_key_exists('height', $args) ? $args['height'] : null;
        $newClass = array_key_exists('class', $args) ? $args['class'] : '';

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($rawSVGString);

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
         * @var $attributes is a store for removal after iterating
         */
        $atts = [];
        foreach ($xml->attributes() as $key => $value) {
            $atts[] = $key;
            $width = strtolower($key) === 'width' ? (string) $value : $width;
            $height = strtolower($key) === 'height' ? (string) $value : $height;
            $viewBox = strtolower($key) === 'viewbox' ? explode(' ', $value) : $viewBox;
        }
        /**
         * Remove all attributes from xml root. Do this in a separate loop
         * to keep from mutating the active iterator. (which doesn't work anyway)
         */
        foreach ($atts as $att) {
            unset($xml->attributes()->$att);
        }

        if (count($viewBox) == 4) {
            $width = $width ?: $viewBox[2];
            $height = $height ?: $viewBox[3];
        }

        if ($width && $height) {
            $aspect = $width / $height;
        }

        /**
         * Restore viewBox width/height and class attributes
         */
        if (count($viewBox) != 4 && $width && $height) {
            $viewBox = [0, 0, $width, $height];
        }

        if (count($viewBox) === 4) {
            $xml->addAttribute('viewBox', implode(' ', $viewBox));
        }

        if ($newWidth == 'auto' && $newHeight == 'auto') {
            $newWidth = $width;
            $newHeight = $height;
        }

        if ($newWidth) {
            if (strtolower($newWidth) == 'auto') {
                $h = $newHeight ?: $height;
                $newWidth = round($h * $aspect);
            }
            $width = $newWidth;
            $xml->addAttribute('width', $newWidth);
        }

        if ($newHeight) {
            if (strtolower($newHeight) == 'auto') {
                $w = $newWidth ?: $width;
                $newHeight = round($w / $aspect);
            }
            $height = $newHeight;
            $xml->addAttribute('height', $newHeight);
        }

        // TOOD: remove this->attributes;
        // $class = $attributes['class'] ?? false;
        if ($newClass) {
            $xml->addAttribute('class', $newClass);
        }

        // $xml->addAttribute('FROG', 'kermit');
        // DEBUG FOR VISIBILITY
        // $xml->addAttribute('style', 'border: 1px dotted cyan');

        /**
         * Remove the XML Declaration
         */
        $clean = preg_replace('/^<\?xml[^<]+/mi', '', $xml->asXML());

        $output = (object) [
            'height' => intval($height),
            'width' => intval($width),
            'aspect' => $aspect,
            'content' => trim($clean),
        ];
        // d($output);
        return $output;
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
     * @return string
     */
    public function normalizeKey($key)
    {
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

        if (!array_key_exists($key, $this->lib) || !property_exists($this->lib[$key], 'content')) {
            if ($this->WP_DEBUG) {
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
            return $this->cleanSvg($name, $args) ?? $this->lib[$name]->content->raw;
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
            echo "\n\n<!-- Loading SVGs from static child classes is deprecated. Load from a directory instead. --> ";
        }
    }

    /**
     * Prints an SVG containing all the symbols referenced in the document.
     */
    public function dumpSymbols()
    {
        if (count($this->inUse)) {
            $this->inUse = array_unique($this->inUse);
            sort($this->inUse);
            $symbols = array_map(function ($key) {
                return preg_replace(
                    ['%<svg .*(viewbox="[^"]*")[^>]*>(.*)%i', '%</svg>%'],
                    ["    <symbol id=\"$key\" $1>$2", '</symbol>'],
                    $this->lib[$key]->content->raw
                );
            }, $this->inUse);
            $symbols = implode("\n", $symbols);
            printf("<svg xmlns='http://www.w3.org/2000/svg' style='display: none;'>\n%s\n</svg>\n", $symbols);
        } else {
            if (is_user_logged_in()) {
                echo "<!-- NO SVGs IN USE -->\n";

                if ($this->WP_DEBUG) {
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
        register_rest_route('ideasonpurpose/v1', '/svg/(?P<name>[^/]*)\.svg', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'returnSvgFile'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('ideasonpurpose/v1', '/svg/(?P<name>[^/]*)', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'restResponse'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('ideasonpurpose/v1', '/svg', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'restResponse'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function returnSvgFile(\WP_REST_Request $req)
    {
        $this->getAttributesFromRestParams($req);

        /**
         * Acceptable false values for 'raw' are ['0', 'no', 'false']
         */
        $raw = $req->get_param('raw');
        $is_raw = !is_null($raw) && !in_array(strtolower($raw), ['0', 'no', 'false']);

        $name = $req->get_param('name');
        if ($this->hasSVG($name)) {
            // NOTE: Disable this to debug SVG contents in the browser
            header('Content-type: image/svg+xml');

            $this->cleanSvg($name);
            if ($is_raw && $this->lib[$name]->content->clean) {
                $svg = $this->lib[$name]->content->raw;
            } else {
                $svg = $this->lib[$name]->content->clean;
            }

            return $this->exit($svg);
        }
    }

    public function restResponse(\WP_REST_Request $req)
    {
        $this->getAttributesFromRestParams($req);

        $name = $this->normalizeKey($req->get_param('name'));

        if ($name && $this->hasSVG($name)) {
            $this->cleanSvg($name);
            return rest_ensure_response($this->lib[$name]);
        }

        foreach (array_keys($this->lib) as $name) {
            /**
             * Skip keys starting with underscores (debug info)
             */
            if (substr($name, 0, 1) == '_') {
                continue;
            }
            $this->cleanSvg($name);
        }
        return rest_ensure_response($this->lib);
    }

    /**
     * Extract and validate known attributes from REST Request Params
     */
    public function getAttributesFromRestParams(\WP_REST_Request $req)
    {
        $params = $req->get_params();

        /**
         * Only store height/width if they are 'auto' or positive integers
         */
        if (preg_match('/^(?:auto|[0-9]+)$/i', @$params['width'])) {
            $this->attributes['width'] = strtolower($params['width']);
        }

        if (preg_match('/^(?:auto|[0-9]+)$/i', @$params['height'])) {
            $this->attributes['height'] = strtolower($params['height']);
        }

        $class = esc_attr(@$params['class']);
        if ($class) {
            $this->attributes['class'] = $class;
        }
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
     * TODO: Add test for bad attributes
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
