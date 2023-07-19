<?php

namespace IdeasOnPurpose\WP\Deprecated;

trait LibFill
{
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
    abstract public function normalizeKey($key);


}
