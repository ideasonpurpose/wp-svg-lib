<?php

namespace IdeasOnPurpose\WP\SVG;

class Shortcodes
{
    public function __construct()
    {
        // add_action('init', [$this, 'addShortcodes']);
        $this->addShortcodes();

    }

    /**
     * Lookup table of slug->function associations for adding shortcodes
     */
    public $codes = ['svg' => 'embedSvg'];

    /**
     * Loops through $this->codes and conditionally adds shortcodes
     */
    public function addShortcodes()
    {

      error_log('in AddShortcodes');
        foreach ($this->codes as $code => $func) {
            if (!shortcode_exists($code)) {
                add_shortcode($code, [$this, $func]);
            }
        }
    }

    /**
     * Embed SVG files
     *
     * This is basically just a wrapper for SVG::embed
     *
     * Example 1: [svg file-slug]
     *
     * Example 2: [svg file-slug height="23" width="auto"]
     *
     * Example 3: [svg src="fileSlug" height="23" width="auto" class="hello there"]
     *
     * TODO: Can Attribute validation be handled by the SVG class??
     */
    public function embedSvg(array $atts, ?string $content = '')
    {
        \Kint::$mode_default = \Kint::MODE_CLI;
        error_log(@d($atts, $content));
        \Kint::$mode_default = \Kint::MODE_RICH;

        return '<strong>SVG GOES HERE</strong>';

        // $munged_mail = antispambot($atts[0]);
        // $content = ($content) ?: $munged_mail;
        // $classes = (isset($atts['class'])) ? "class=\"$atts[class]\"" : '';
        // return "<a $classes href='mailto:$munged_mail'>$content</a>";
    }
}
