<?php

namespace IdeasOnPurpose\WP\Deprecated;

trait DumpSymbols
{
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
            printf(
                "<svg xmlns='http://www.w3.org/2000/svg' style='display: none;'>\n%s\n</svg>\n",
                $symbols
            );
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
}
