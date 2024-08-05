<?php

namespace IdeasOnPurpose\WP\Deprecated;

trait GetSVG
{
    /**
     * Alias for $this->fetch()
     * TODO: Pick a name
     * @param mixed $key
     * @param mixed $attributes
     * @deprecated
     * @return void
     */
    public function getSVG($key, $attributes = [])
    {
        return $this->fetch($key, $attributes);
    }

    abstract public function fetch($key, $attributes = []);
}
