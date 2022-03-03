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

    public function __construct($libDir = null)
    {
        $this->is_debug = defined('WP_DEBUG') && WP_DEBUG;

        $this->inUse = [];
        $this->loadFromDirectory($libDir);
        $this->libNormalizeKeys();
        $this->libFill();

        // d($this->lib);
        // // TODO: Should this only happen if there are SVGs in the library?
        // //       How to make sure they appear if a second directory is loaded?

        // add_action('pre_get_posts', function () {
        //     set_query_var('SVG', $this);
        // });

        add_action('pre_get_posts', [$this, 'registerQueryVar']);
        add_action('wp_footer', [$this, 'dumpSymbols']);
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
    public function loadFromDirectory($dir = null)
    {
        // Do we need to resolve this path? What happens when we send something relative?
        // $dir = $dir ?? $this->libDir;
        // if ($this->libDir && file_exists($this->libDir) && is_dir($this->libDir)) {

        if (!$dir || !file_exists($dir) || !is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveDirectoryIterator($dir);
        foreach (new \RecursiveIteratorIterator($iterator) as $file) {
            if (strtolower($file->getExtension()) === 'svg') {
                $key = str_replace($dir, '', $file->getPathname());
                $key = ltrim($key, '/');
                $key = preg_replace('/\.svg$/i', '', $key);
                $this->lib[$key] = trim(file_get_contents($file->getRealPath()));
            }
        }
    }

    /**
     * Normalize keys to camelCase then replace path-separators with double-underscores
     * If the key does not already exist in $this->lib, link the new key to the original
     */
    private function libNormalizeKeys()
    {
        $inflector = InflectorFactory::create()->build();
        foreach ($this->lib as $key => $svg) {
            $newKey = $inflector->camelize($key);
            $newKey = preg_replace('/\//', '__', $newKey);
            if (!array_key_exists($newKey, $this->lib)) {
                $this->lib[$newKey] = $this->lib[$key];
            }
        }
    }

    /**
     * Check if SVG exists and prints debugging messages if missing
     */
    private function hasSVG($key)
    {
        if (!array_key_exists($key, $this->lib)) {
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
     */
    public function embed($name)
    {
        $basename = preg_replace('/\.svg$/i', '', $name);

        if ($this->hasSVG($basename)) {
            return $this->lib[$basename];
        }
    }

    /**
     * include SVGs as linked symbols
     * This replaces the legacy SVG::get method
     *
     * '.svg' extensions are stripped, so 'arrow' and 'arrow.svg' will both return the 'arrow.svg' file
     */
    public function use($name)
    {
        $basename = preg_replace('/\.svg$/i', '', $name);

        if ($this->hasSVG($basename)) {
            array_push($this->inUse, $basename);
            return sprintf(
                '<svg class="%1$s"><use xlink:href="#%1$s" href="#%1$s" /></svg>',
                $basename
            );
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
     */
    private function libFill()
    {
        $static_vars = get_class_vars(get_called_class());
        foreach ($static_vars as $key => $svg) {
            if (is_string($svg) && substr($svg, 0, 4) == '<svg') {
                // $this->lib[$key] = preg_replace(
                //     ['%<svg .*(viewbox="[^"]*")[^>]*>(.*)%i', '%</svg>%'],
                //     ["    <symbol id=\"$key\" $1>$2", '</symbol>'],
                //     $svg
                // );
                $this->lib[$key] = $svg;
            }
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
                    $this->lib[$key]
                );
            }, $this->inUse);
            $symbols = implode("\n", $symbols);
            printf(
                "<svg xmlns='http://www.w3.org/2000/svg' style='display: none;'>\n%s\n</svg>\n",
                $symbols
            );
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
}
