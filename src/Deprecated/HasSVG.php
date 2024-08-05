<?php

namespace IdeasOnPurpose\WP\Deprecated;

trait HasSVG
{
    /**
     * Check if SVG exists
     * @deprecated Renamed to `exists`
     */
    public function hasSVG($name)
    {
        return $this->exists($name);
    }

    abstract public function exists($name);
}
