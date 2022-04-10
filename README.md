# wp-svg-lib

#### Version 2.0.0

A simple library to assist with inlining and re-using SVG elements on WordPress sites.

[![Packagist](https://badgen.net/packagist/v/ideasonpurpose/wp-svg-lib)](https://packagist.org/packages/ideasonpurpose/wp-svg-lib)
[![codecov](https://codecov.io/gh/ideasonpurpose/wp-svg-lib/branch/master/graph/badge.svg)](https://codecov.io/gh/ideasonpurpose/wp-svg-lib)
[![Coverage Status](https://coveralls.io/repos/github/ideasonpurpose/wp-svg-lib/badge.svg)](https://coveralls.io/github/ideasonpurpose/wp-svg-lib)
[![Maintainability](https://api.codeclimate.com/v1/badges/c5828971734cecd15cd0/maintainability)](https://codeclimate.com/github/ideasonpurpose/wp-svg-lib/maintainability)
[![styled with prettier](https://img.shields.io/badge/styled_with-prettier-ff69b4.svg)](https://github.com/prettier/prettier)

## What it does

This helper library reads SVG files from a directory then provides helper functions for embedding the files into WordPress templates. Files can be embedded inline or converted to symbols to help conserve bandwidth.

_**Note:** Version 2.0.0 changed the namespace from `IdeasOnPurpose\SVG` to `IdeasOnPurpose\WP\SVG`._

## Instructions

Initialize the library in your **functions.php** file like this:

```php
new IdeasOnPurpose\WP\SVG(__DIR__ . '/dist/images/svg');
```

Every SVG file in that directory or its children will be registered. The library will also inject an `$SVG` query var so SVGs can be accessed from inside [`get_template_part()`][gtp] includes with no additional code.

All SVGs are indexed by their filename and containing path.

### Embedding SVGs

Embedding images is the same as pasting the SVG files into the HTML source. Most registered SVG files can be inserted using just their name, so a file named **logo.svg** can embedded like this:

```php
<div><?= $SVG->logo ?></div>
```

That code outputs something like this:

```html
<div><svg viewBox="0 0 25 10">...</svg></div>
```

The library will attempt to normalize all file names to camelCase to help with embedding. Directory separators will be replaced with double-underscores. Some examples:

```php
// the file 'icons/email-circle.svg' can be embedded as:
$SVG->icons__emailCircle;
```

For SVG files whose names aren't compatible with PHP's property syntax or are nested subfolders, there's also an embed command:

```php
<li><?= $SVG->embed('arrow-left') ?></li>
<li><?= $SVG->embed('icons/email') ?></li>
```

For convenience, SVG files can be embedded with or without their file extension. So the previous embeds could also work like this:

```php
<li><?= $SVG->embed('arrow-left.svg') ?></li>
<li><?= $SVG->embed('icons/email.svg') ?></li>
```

### Inlining SVG Symbols

SVGs can also be injected as linked symbols, where most all of the markup only appears once. This can be useful for simple elements which appear repeatedly:

```html
<a href="#"><?= $SVG->get('arrow') ?>Go!</a>
```

The library keeps a record of which files have been included like this, then injects a symbol reference from the `wp_footer` hook. Together, the above code and symbol library look like this:

```html
<a href="#"><svg class="arrow"><use xlink:href="#arrow" href="#arrow" /></svg>Go!</a>

<svg xmlns='http://www.w3.org/2000/svg' style='display: none;'>
    <symbol id="arrow" viewBox="0 0 50 50">...</symbol>
</svg>
</body>
```

## REST API

This library adds the `/ideaosnpurpose/v1/svg` endpoint to the WP-JSON API.

If either height or width are 'auto' then that value will be calculated from the aspect ratio and the opposite dimenision.

## Installation

This library is available on [Packagist](https://packagist.org/packages/ideasonpurpose/wp-svg-lib), to use it, require it in **composer.json** or tell Composer to load the package:

```bash
$ composer require ideasonpurpose/wp-svg-lib
```

### Notes

SVG files are not optimized in any way. Please use something like [svgo][] or [our buildchain][docker-build] to optimize SVG files.

The **posttest** package.json script is a workaround to remap files paths because PHPUnit writes absolute paths into its coverage files. Since those paths are from inside a Docker image, they don't exist. I couldn't find another workaround which let me display coverage in VS Code.

---

\*\*TODO: implement shortcode

```
[svg file-slug]

[iop-svg file-slug height="23" width="auto"]
[iop-svg src="fileSlug" height="23" width="auto" class="hello there"]

// necessary to quote attributes? Even simple numbers?
```

> WIP SVG REST API notes:

TODO: If the SVG can be parsed as XML, then we can read attributes

The goal would be to normalize dimensions, an potentially provide
functionality to enforce dimensions.

eg. a request for /wp-json/ideasonpurpose/v1/svg/arrow?w=48
would return the SVG with updated width/height tags, derived
from the viewBox dimensions.

If the raw opening tag looked like this:

    <svg viewBox="0 0 150 100">

The SVG API would return something this:

    <svg width="48" height="32" viewBox="0 0 150 100">

Questions: - Q. Should existing height/width tags be rewritten?
A. yes, if explicitly requested.

    - Q. What happens to invalid SVGs?
        A. No idea? Pass through unchanged?
        There should be some notification, no quiet failures.
        (Is that a failure? -- Yes, any deviation from expected behavior should be explained)

    - Q. What happens if there's no viewBox?
        A. Pass through unchanged.
        I don't want to try and derive a viewBox from potentially missing height/width attributes

**NOTE** Name resolution is now bi-directional. So if an SVG file named **dash-case.svg** is registered, that file can be embedded by either `dash-case`, `dashCase`. Likewise, a file named **camelCase.svg** will be accessible by either `camelCase` or `camel-case.

---

[svgo]: https://www.npmjs.com/package/svgo
[docker-build]: https://github.com/ideasonpurpose/docker-build
[gtp]: https://developer.wordpress.org/reference/functions/get_template_part/
