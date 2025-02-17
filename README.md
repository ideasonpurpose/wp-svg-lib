# wp-svg-lib

#### Version 3.1.0

A powerful utility library for including SVG files in WordPress sites.

[![Packagist](https://badgen.net/packagist/v/ideasonpurpose/wp-svg-lib)](https://packagist.org/packages/ideasonpurpose/wp-svg-lib)
[![codecov](https://codecov.io/gh/ideasonpurpose/wp-svg-lib/branch/master/graph/badge.svg)](https://codecov.io/gh/ideasonpurpose/wp-svg-lib)
[![Coverage Status](https://coveralls.io/repos/github/ideasonpurpose/wp-svg-lib/badge.svg)](https://coveralls.io/github/ideasonpurpose/wp-svg-lib)
[![Maintainability](https://api.codeclimate.com/v1/badges/c5828971734cecd15cd0/maintainability)](https://codeclimate.com/github/ideasonpurpose/wp-svg-lib/maintainability)
[![styled with prettier](https://img.shields.io/badge/styled_with-prettier-ff69b4.svg)](https://github.com/prettier/prettier)

## What it does

This helper library reads SVG files from a directory then provides helper functions for embedding the files into WordPress templates. Files can be embedded inline or converted to symbols to help conserve bandwidth.

The library also enables SVG shortcodes, which can directly place SVGs into the block editor. 

_**Note:** Version 2.0.0 changed the namespace from `IdeasOnPurpose\SVG` to `IdeasOnPurpose\WP\SVG`._

## Instructions

Initialize the library from a theme's **functions.php** file. Starting with version 3, the library looks for SVG files in the theme's `dist/images/svg` directory by default, so instantiation can look like this:

```php
new IdeasOnPurpose\WP\SVG;

// or use a custom path
new IdeasOnPurpose\WP\SVG(get_theme_directory() . '/icons/svg');
```

All SVG files below the directory will be registered and available to template files. The library will inject an `$SVG` query var so SVGs can be accessed from inside [`get_template_part()`][gtp] includes with no additional code.

Install from [Packagist](https://packagist.org/packages/ideasonpurpose/wp-svg-lib), require it in **composer.json** or tell Composer to load the package:

```bash
$ composer require ideasonpurpose/wp-svg-lib
```

### Embedding SVGs

Embedding images is the same as pasting the SVG files into the HTML source. Most registered SVG files can be inserted using just their name, so a file named **logo.svg** can embedded like this:

```php
<div><?= $SVG->logo ?></div>
```

That code outputs something like this:

```html
<div><svg viewBox="0 0 25 10">...</svg></div>
```

The library normalizes all file names to _camelCase_ to help with embedding. Directory separators will be replaced with double-underscores. Some examples:

```php
// the file 'icons/email-circle.svg' can be embedded as:
$SVG->icons__emailCircle;
```

For SVG files whose names aren't compatible with PHP's property syntax, there's also an embed command which also works with nested directories:

```php
<li><?= $SVG->embed('arrow-left') ?></li>
<li><?= $SVG->embed('icons/email') ?></li>
```

For convenience, SVG files can be embedded with or without their file extension. So the previous embeds could also work like this:

```php
<li><?= $SVG->embed('arrow-left.svg') ?></li>
<li><?= $SVG->embed('icons/email.svg') ?></li>
```

Note that name resolution is bi-directional. An SVG file named **dash-case.svg** can be embedded by either `dash-case` or `dashCase`. Likewise, a file named **camelCase.svg** will be accessible by either `camelCase` or `camel-case`.

### Inlining SVG Symbols

SVGs can also be injected as linked symbols, where most all of the markup only appears once. This can be useful for simple elements which appear repeatedly:

```php
<a href="#"><?= $SVG->use('arrow') ?>Go!</a>
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

This library adds the `/ideasonpurpose/v1/svg` endpoint to the WP-JSON API.

Files can be requested by name like this:

- https://example.com/wp-json/ideasonpurpose/v1/svg/arrowLeft
- https://example.com/wp-json/ideasonpurpose/v1/svg/icons__email

Dimensions, classes and IDs can be injected using query vars:

- https://example.com/wp-json/ideasonpurpose/v1/svg/arrowLeft?width=200&height=auto
- https://example.com/wp-json/ideasonpurpose/v1/svg/icons__email&class=social+blue

A listing of all registered SVGs is here:

- https://example.com/wp-json/ideasonpurpose/v1/svg/

If either height or width are 'auto' then that value will be calculated from the aspect ratio and the opposite dimension.

Well-formed SVG files should return a data object like this:

```json

{
  "svg": "<svg viewBox=\"0 0 25 10\" xmlns=\"http://www.w3.org/2000/svg\"><path fill-rule=\"evenodd\" d=\"M0 5.57.01 4.4c12.59.08 18.84.08 18.77 0V0L25 4.99 18.77 10l.01-4.43H0Z\"/>\n</svg>",
  "innerContent": "<path fill-rule=\"evenodd\" d=\"M0 5.57.01 4.4c12.59.08 18.84.08 18.77 0V0L25 4.99 18.77 10l.01-4.43H0Z\"/>\n",
  "width": 25,
  "height": 10,
  "aspect": 2.5,
  "attributes": {
    "viewBox": "0 0 25 10"
  },
  "__srcPath": "/var/www/html/wp-content/themes/example/dist/images/svg/arrow.svg",
  "_links": {
    "self": "https://example.com/wp-json/ideasonpurpose/v1/svg/arrow",
    "collection": "https://example.com/wp-json/ideasonpurpose/v1/svg",
    "svg": "https://example.com/wp-json/ideasonpurpose/v1/svg/arrow?svg=1",
    "src": "https://example.com/wp-content/themes/example/dist/images/svg/arrow.svg"
  }
}
```

### Properties:

* `svg` - A rewrapped SVG with a cleaned subset of attributes
* `innerContent` - The contents of the SVG with enclosing tags stripped off. Used in the Block Editor in place of pre-processed [SVGR objects](https://react-svgr.com/docs/what-is-svgr/). 
* `width` - Derived width of the file, either extracted from viewBox or read from attributes.
* `height` - Derived height of the file, either extracted from viewBox or read from attributes.
* `aspect` - Convenience value, derived width divided by derived height. 
* `attributes` - Array of attributes to appear in the `<svg>` tag. Valid attributes include: `id`, `class`, `height`, `width` and `viewBox`
* `_links` - Array of related URLs:
  * `self`
  * `collection` (all available SVGs)
  * `svg` - Returns with `Content-type: image/svg+xml` headers and can be used to display the SVG file directly in the browser.
  * `src` - Direct link to the unmodified source SVG file
* `original_attributes` - The unmodified array of cleaned attributes. Only appears for requests with modified attributes. 
* `__srcPath` - Filesystem path of the source SVG. _[Debug only]_ 


### Removed Attributes and Optimization

All attributes except `viewBox` and `xmlns` are removed from`clean` valid SVG files, but the unmodified `src` files are also available.

Invalid SVGs pass through to `src` without modification. Error details will be added to the JSON data object.

Other than the opening `<svg>` tag, vector data is not optimized in any way. Please use something like [svgo][] to optimize SVG files.

## Shortcodes

> _In Progress_

SVGs can be included by authors and for Full Site Editing using shortcodes.

```
[svg file-slug]
[svg file-slug height="23" width="auto"]
[svg src="fileSlug" height="23" width="auto" class="hello there"]
```

A single bare attribute will be treated as the source, but the shortcode prefers named attributes and if a shortcode contains both, the named `src` attribute will override the positional info.

In this example, the `square` SVG will be shown instead of `circle`:

```
[svg circle src="square"]
```

#### _Notes_

- using `svg` as the Shortcode name may come back to haunt us. But I'd rather keep the shortcode simple and memorable.
- Is it necessary to quote shortcode attributes?

* What happens to invalid SVGs?

  - A. No idea? Pass through unchanged?
    There should be some notification, no quiet failures.
    (Is that a failure? -- Yes, any deviation from expected behavior should be explained)

* What happens if there's no viewBox?
  - ViewBox will be added if it can be derived from supplied `height` and `width` attributes. If there are no dimensions and no viewBox, nothing will be added to the opening `<svg>` tag.

### Cleaning Quirks

`<svg></svg` is a valid, though useless SVG document. Internally, the cleaner will returned zero for width and height, with an aspect ratio of 1.

[svgo]: https://www.npmjs.com/package/svgo
[docker-build]: https://github.com/ideasonpurpose/docker-build
[gtp]: https://developer.wordpress.org/reference/functions/get_template_part/
