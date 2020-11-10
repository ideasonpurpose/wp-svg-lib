<?php
namespace IdeasOnPurpose;

/**
 * A library for embedding SVGs from the filesystem
 *
 * SVGs can be embedded as symbols with $SVG->use('name')
 * SVGs can be embedded directly through magic methods like these:
 *      $SVG->name
 *      $SVG->arrow
 *
 * A legacy method $SVG->get('name') is supported as an alias of `use`
 */
class SVG
{
    // Test circle
    public static $test = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><circle cx="100" cy="100" r="100"/></svg>';

    private $lib = [];
    public $libDir = null;

    public function __construct($libDir = null)
    {
        $this->inUse = [];
        $this->libDir = $libDir ? $libDir : $this->libDir;
        $this->loadFromDirectory();
        $this->libFill();

        // @codeCoverageIgnoreStart
        add_action('pre_get_posts', function () {
            set_query_var('SVG', $this);
        });

        add_action('wp_footer', [$this, 'dumpSymbols']);
        // @codeCoverageIgnoreEnd
    }

    /**
     * Checks the `$this->libDir` for SVG files and includes
     * any found using the files' baseName as the storage key.
     */
    public function loadFromDirectory()
    {
        if ($this->libDir && file_exists($this->libDir) && is_dir($this->libDir)) {
            $iterator = new \RecursiveDirectoryIterator($this->libDir);
            foreach (new \RecursiveIteratorIterator($iterator) as $file) {
                if (strtolower($file->getExtension()) === 'svg') {
                    $key = str_replace($this->libDir, '', $file->getPathname());
                    $key = preg_replace('/\.svg$/i', '', $key);
                    $key = ltrim($key, '/');

                    $this->lib[$key] = trim(file_get_contents($file->getRealPath()));
                }
            }
        }
    }

    /**
     * Alias for `debug`
     * DEPRECATED
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
            printf('<div>%s</div>', $this->get($key));
            echo "<div style='color: #bbb'>\$SVG->get(\"<span style='color:#c00'>$key</span>\")</div>";
        }
        echo '</div>';
    }

    /**
     * Copies static variables into $this->lib
     * This is largely for compatibility since we've previously been echoing static variables as needed
     * In the future, this can be refactored away and SVG content can be directly entered into $this->lib
     *
     * Note: `get_class_vars(get_called_class())` pulls static vars in from the child class
     */
    private function libFill()
    {
        $static_vars = get_class_vars(get_called_class());
        foreach ($static_vars as $key => $svg) {
            if (is_string($svg) && substr($svg, 0, 4) == '<svg') {
                $this->lib[$key] = preg_replace(
                    ['%<svg .*(viewbox="[^"]*")[^>]*>(.*)%i', '%</svg>%'],
                    ["    <symbol id=\"$key\" $1>$2", '</symbol>'],
                    $svg
                );
            }
        }
    }

    /**
     * Records a symbol as being used, then returns an SVG "use" reference to that symbol
     *
     * Writes an error message in an HTML comment if an SVG can not be found.
     */
    public function get($key)
    {
        if (!array_key_exists($key, $this->lib)) {
            error_log("SVG Lib Error: The key '$key' does not match any registered SVGs");
            return "\n<!-- SVG Lib Error: The key '$key' does not match any registered SVGs -->\n\n";
        }
        array_push($this->inUse, $key);
        return sprintf('<svg class="%1$s"><use xlink:href="#%1$s" href="#%1$s" /></svg>', $key);
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
                    $this->lib[$key]
                );
            }, $this->inUse);
            printf(
                "<svg xmlns='http://www.w3.org/2000/svg' style='display: none;'>\n%s\n</svg>\n",
                implode("\n", $symbols)
            );
        } else {
            if (is_user_logged_in()) {
                printf("\n<!-- NO SVGs IN USE -->\n<!-- message from %s -->\n\n", __FILE__);
            }
        }
    }
}
