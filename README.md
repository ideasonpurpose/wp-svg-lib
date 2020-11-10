# wp-svg-lib

#### Version 0.4.0

A simple library to assist with inlining and re-using SVG elements on WordPress sites.

## What it does

SVG files are pulled from a common library. When a file is used, it's injected as a symbol which references an SVG symbol library which is injected intop the page footer.

## How it works

This command will inline an SVG file into the page:

```
<?= $SVG->get('site-logo') ?>
```

The `get()` method always returns a string.

The `$SVG` query var is added via `pre_get_posts`, so the library is available as a pseudo-global inside [`get_template_part()`][gtp] calls.

## Using an SVG Library

A directory of SVG files can be loaded for use. Initialize the library with a path like this:

```
<?php
new ideasonpurpose\SVG\SVG('/path/to/svg-library');
```

Ideally this library contains pre-optimized files. SVGs may be run through something like [svgo][] (or our [buildchain][iop buildchain]) then copied to a persistent SVG directory.

### Notes

Filenames will be normalized to lowercase. All storage keys will be lowercase. So `Logo.svg` will be stored under the key `logo` and `Portrait.SVG` will be stored under `portrait`.

The **posttest** script is a workaround to remap files paths because PHPUnit writes absolute paths into its coverage files. Because those paths are from inside a Docker image, those paths don't exist. I couldn't find another workaround which let me display coverage in VS Code.

<!-- TOOD: Fix that? Seems pathetic -->

[svgo]: https://www.npmjs.com/package/svgo
[iop buildchain]: https://github.com/ideasonpurpose/docker-build
[gtp]: https://developer.wordpress.org/reference/functions/get_template_part/
