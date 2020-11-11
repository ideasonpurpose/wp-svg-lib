# wp-svg-lib

#### Version 0.4.0

A simple library to assist with inlining and re-using SVG elements on WordPress sites.

[![Travis Build Status](https://img.shields.io/travis/ideasonpurpose/wp-svg-lib?logo=travis)](https://travis-ci.org/ideasonpurpose/wp-svg-lib)
[![codecov](https://codecov.io/gh/ideasonpurpose/wp-svg-lib/branch/master/graph/badge.svg)](https://codecov.io/gh/ideasonpurpose/wp-svg-lib)
[![Coveralls github](https://img.shields.io/coveralls/github/ideasonpurpose/wp-svg-lib?label=Coveralls)](https://coveralls.io/github/ideasonpurpose/wp-svg-lib)
[![Code Climate maintainability](https://img.shields.io/codeclimate/maintainability/ideasonpurpose/wp-svg-lib)](https://codeclimate.com/github/ideasonpurpose/wp-svg-lib)
[![styled with prettier](https://img.shields.io/badge/styled_with-prettier-ff69b4.svg)](https://github.com/prettier/prettier)

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
