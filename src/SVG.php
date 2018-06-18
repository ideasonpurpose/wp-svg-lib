<?php
namespace ideasonpurpose\SVG;

/**
 * Collection of reusable, optimized SVG elements.
 *
 * TODO: Handle missing SVGs
 * TODO: Load SVGs from the filesystem and convert them to symbols
 */
class SVG
{
    // Test circle
    public static $test = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><circle cx="100" cy="100" r="100"/></svg>';

    private $lib = [];

    public function __construct()
    {
        $this->inUse = [];
        $this->libFill();
        add_action('wp_footer', [$this, 'dumpSymbols']);
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
     */
    public function get($key)
    {
        array_push($this->inUse, $key);
        return sprintf(
            '<svg><use xlink:href="#%1$s" href="#%1$s" /></svg>',
            $key
        );
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
                return $this->lib[$key];
            }, $this->inUse);
            printf(
                "<svg xmlns='http://www.w3.org/2000/svg' style='display: none;'>\n%s\n</svg>\n",
                implode("\n", $symbols)
            );
        } else {
            if (is_user_logged_in()) {
                echo "\n<!-- NO SVGs IN USE -->\n";
                printf("\n<!-- message from %s -->\n\n", __FILE__);
            }
        }
    }
}
