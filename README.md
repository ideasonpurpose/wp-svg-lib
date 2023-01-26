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

Initialize the library from a theme's **functions.php** file. Starting with version 3, the library looks for SVG files in the theme's `dist/images/svg` directory by default, so instantiation can look like this:

```php
new IdeasOnPurpose\WP\SVG;

// or use a custom path
new IdeasOnPurpose\WP\SVG(get_theme_directory() . '/icons/svg');
```

Every SVG file in that directory or its children will be registered and available to template files. The library will inject an `$SVG` query var so SVGs can be accessed from inside [`get_template_part()`][gtp] includes with no additional code.

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

The library will normalize all file names to camelCase to help with embedding. Directory separators will be replaced with double-underscores. Some examples:

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

```php
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

This library adds the `/ideasonpurpose/v1/svg` endpoint to the WP-JSON API.

Files can be requested by name like this:

- https://example.com/ideasonpurpose/v1/svg/arrowLeft
- https://example.com/ideasonpurpose/v1/svg/icons__email

Dimensions and classes can be injected using query vars:

- https://example.com/ideasonpurpose/v1/svg/arrowLeft?width=200&height=auto
- https://example.com/ideasonpurpose/v1/svg/icons__email&class=social+blue

A listing of all registered SVGs is here:

- https://example.com/ideasonpurpose/v1/svg/

If either height or width are 'auto' then that value will be calculated from the aspect ratio and the opposite dimension.

Well-formed SVG files should return a data object like this:

```json
{
  "icons__email": {
    "content": {
      "raw": "<svg height=\"50\" width=\"48\" role=\"img\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 496 512\"><path d=\"M16 32c0z\"/></path></svg>",
      "clean": "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 496 512\"><path d=\"M16 32c0z\"/></path></svg>\n"
    },
    "src": "dist/images/svg/icons/email.svg",
    "_links": {
      "self": "https://example.com/wp-json/ideasonpurpose/v1/svg/icons__email",
      "collection": "https://example.com/wp-json/ideasonpurpose/v1/svg",
      "raw": "https://example.com/wp-json/ideasonpurpose/v1/svg/icons__email.svg?raw",
      "clean": "https://example.com/wp-json/ideasonpurpose/v1/svg/icons__email.svg"
    },
    "width": 48,
    "height": 50,
    "aspect": 0.96
  }
}
```

The `_links.raw` and `_links.clean` values (endpoints include the `.svg` extension) will return with `Content-type: image/svg+xml` headers and can be used to display the SVG file directly in the browser. 

### Removed Attributes and Optimization

All attributes except `viewBox` and `xmlns` are removed from`clean` valid SVG files, but the `raw` unmodified original files are also available.

Invalid SVGs pass through to `raw` without modification. Error details will be added to the JSON data object.

Other than the opening `<svg>` tag, vector data is not optimized in any way. Please use something like [svgo][] or [our buildchain][docker-build] to optimize SVG files.

## Notes

- Name resolution is bi-directional. An SVG file named **dash-case.svg** can be embedded by either `dash-case` or `dashCase`. Likewise, a file named **camelCase.svg** will be accessible by either `camelCase` or `camel-case`.

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
