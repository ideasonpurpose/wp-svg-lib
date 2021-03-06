# wp-svg-lib

#### Version 1.2.0

A simple library to assist with inlining and re-using SVG elements on WordPress sites.

[![Travis Build Status](https://img.shields.io/travis/ideasonpurpose/wp-svg-lib?logo=travis)](https://travis-ci.org/ideasonpurpose/wp-svg-lib)
[![codecov](https://codecov.io/gh/ideasonpurpose/wp-svg-lib/branch/master/graph/badge.svg)](https://codecov.io/gh/ideasonpurpose/wp-svg-lib)
[![Coveralls github](https://img.shields.io/coveralls/github/ideasonpurpose/wp-svg-lib?label=Coveralls)](https://coveralls.io/github/ideasonpurpose/wp-svg-lib)
[![Code Climate maintainability](https://img.shields.io/codeclimate/maintainability/ideasonpurpose/wp-svg-lib)](https://codeclimate.com/github/ideasonpurpose/wp-svg-lib)
[![styled with prettier](https://img.shields.io/badge/styled_with-prettier-ff69b4.svg)](https://github.com/prettier/prettier)

## What it does

This helper library reads SVG files from a directory then provides helper functions for embedding the files into WordPress templates. Files can be embedded inline or converted to symbols to help conserve bandwidth.

## Instructions

Initialize the library in your **functions.php** file like this:

```php
new IdeasOnPurpose\SVG(__DIR__ . '/dist/images/svg');
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
</symbol>
</body>
```

## Installation

Add an entry to the **composer.json** `repositories` property:

```json
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/ideasonpurpose/wp-svg-lib.git",
      "no-api": true
    }
  ]
```

Then require from composer:

```sh
composer require ideasonpurpose/wp-svg-lib:^1.0.0
```

### Notes

SVG files are not optimized in any way. Please use something like [svgo][] or [our buildchain][docker-build] to optimize SVG files.

The **posttest** package.json script is a workaround to remap files paths because PHPUnit writes absolute paths into its coverage files. Since those paths are from inside a Docker image, they don't exist. I couldn't find another workaround which let me display coverage in VS Code.

[svgo]: https://www.npmjs.com/package/svgo
[docker-build]: https://github.com/ideasonpurpose/docker-build
[gtp]: https://developer.wordpress.org/reference/functions/get_template_part/
