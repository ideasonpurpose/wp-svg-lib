<?php

namespace IdeasOnPurpose\WP\Deprecated;

trait Get
{
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

    abstract public function use($key);

}
