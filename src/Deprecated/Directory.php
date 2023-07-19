<?php

namespace IdeasOnPurpose\WP\Deprecated;

trait Directory
{
    /**
     * Alias for `debug`
     * @deprecated
     */
    public function directory()
    {
        echo "\n\n<!-- The directory method is deprecated --> ";
        $this->debug();
    }

    abstract public function debug();

}
