Fluid Content: Core Elements
============================

> A replacement for `EXT:css_styled_content` 100% powered by Fluid and Flux Forms.

## What does it do?

Just like `EXT:css_styled_content` this extension adds rendering instructions for records from tt_content. But rather than use
TypoScript to manipulate rendering, each content element type is assigned one Fluid template and is _entirely rendered in Fluid_.

## Does it actually work? There's no code!?

Sure, buddy. Go ahead and try it out! At the time of writing this, though, most of the content templates are "Hello world".
That'll change though - a sane, very basic set of default templates is intended.

## How do I install it?

1. Download the extension and install it.
2. Copy, or integrate into your own, the AdditionalConfiguration.php file from `EXT:fluidcontent_core/Build/AdditionalConfiguraion.php`
   into `typo3conf/AdditionalConfiguration.php`

The second step although more manual than is desirable, is necessary in order to ensure that every plugin and content type that
is added, will add their rendering instructions on top of the TypoScript `fluidcontent_core` will load, regardless of that
TypoScript being loaded before or after `fluidcontent_core` itself. It's not magic - but it slightly esoteric (mostly because it
exploits one of the oldest features in TYPO3; so-called "content rendering templates" which will be loaded before all other
TypoScript).

There is no static TypoScript to load; this happens automatically. Simply install, use `AdditionalConfiguration.php` and your
TYPO3 site will begin using the Fluid templates. Read more in this README file about how to create your own templates.

## Why use it?

It does not get any more flexible than this when it comes to custom styled TYPO3 CMS sites. You can replace any number of built-in
content elements with your own Fluid templates. You can switch which templates get used on individual subpages (using TypoScript
to override view paths). You can use template path overlays like you know from `EXT:view`. Instead of using either a FlexForm or
adding TCA/DB columns, you can simply insert a Flux form definition in the template file and add any number of fields you wish to
use to control rendering.

You can even throw any number of these built-in content element types into a Flux Provider extension and add (static) TypoScript
setup to make this extension use your template paths first, with fallback to the included templates. Note, though, that you
should not mix these core content element templates with your own custom elements for `EXT:fluidcontent`.

## Is it faster than `css_styled_content`?

Quite a bit, yes. Initial measurements gave a 400% increase in speed for cached pages simply due to the fact that there is around
10% of the TypoScript to load (mind you, TypoScript actually gets loaded on cached pages, too, and often account for more than
50% of the total frontend loading times of cached pages). So yes, it is quite a lot faster on cached pages. But it can be slightly
slower when generating the cached content and if you intentionally disable caching. You win some, you lose some.

## Examples

Built-in template files are under `Resources/Private/Templates/Content` - they all share one Layout file and use one Partial
template to render a shared header (equivalent of `lib.stdheader` in `EXT:css_styled_content`). The individual plugins get
configured in `ext_localconf.php` (one plugin per content type, matching each possible `CType` value) and a tiny little bit of
TypoScript is used to build a very basic `tt_content.*` array which is required in order to render anything. This, along with the
template path definitions you already know - and any custom settings you choose to add and thereby make available in templates -
is the only TypoScript needed by this extension. The view definitions are exactly like you know from any Extbase plugin.

In essence, this is an Extbase plugin to replace the TypoScript based rendering of `EXT:css_styled_content` and leveraging Flux
to make it extremely easy to configure exactly the right fields you want to use to configure your contents' appearance.

This is very likely to be one of the simplest extensions you have ever used and everything builds on conventions from both Flux,
Extbase and Fluid to give you the ultimate degree of flexibility.

There are three main ways to get your own content element templates to replace core content element templates. Each has a very
specific use case - your responsibility as extension developer or integrator respectively, is to pick the correct one so that your
template collection will integrate perfectly.

## Concept

FluidcontentCore enables three levels of content usage, in order of simplicity:

1. Replacing one or more content element types with custom Fluid templates.
2. Providing further variations of each content element type, for example "text" elements from multiple extensions, for example
   fitting different CSS frameworks, which can be configured to be used by default.
3. Providing additional versions of each content element type variation, for example when a Twitter Bootstrap text element is
   selected, you may want to provide a version that uses `<p class="lead"></p>` and supports the `text-warning` etc. CSS classes.

The first approach is the one you know from any Fluid context in TYPO3. The second can be explained as `fluidcontent_bootstrap`
for TYPO3's built-in content elements (as in: whereas the actual `fluidcontent_bootstrap` only **adds** new content types, this
concept allows adding also new variations of core content elements - without replacing the standard ones). The third approach can
be viewed as a way to version each variant you add: for example, copying existing elements to a legacy template when you make
changes, trying out new versions of your content without replacing the current ones, or simply dividing one variant of a content
element into multiple specialised "rendering versions" like in the Twitter Bootstrap example above.

A more detailed description follows in these chapters.

### Concept: Overlaying and Overriding

> Use case: forceful overriding of existing templates to use your own templates always

This is the replace-them-all or replace-a-couple strategy that is extremely well known by now - suffice it to write that you can
configure an alternative `templateRootPath` etc. for FluidcontentCore to use, and that when you do this every template file must
then exist in this other path. Or, less well known but hopefully familiar to you, the "overlay" approach implemented by fx
EXT:view and EXT:fluidpages which let you replace one (or any number of) template files rather than having to replace them all.

You can use this concept to make a completely new "package" of core content that is rendered using your custom templates. You can
use "overlays" when you don't want to copy all the template files.

### Concept: Variants

> Use case: extension xyz would like to include an alternative "Text" or other type of core content element that editors can use,
> __in addition to__ the core elements that exist in the default or overridden template paths configured in TypoScript.

Note the "in addition to" part carefully. This is why this concept is called "variants" and not "replacements". Use this concept
when you do not wish to replace existing templates but rather provide alternative (variant) templates that can **also** be used.
With template path overriding or overlays you get **one** "variant" as result (the one you override or overlay). With this
concept you get **two** "variants" - the basic one (potentially overlayed or overridden) **and** your added one.

The `Variant` concept simply means you can add any number of variations of each content type, each provided by an extension. To
tell FluidcontentCore about your variants, add this code:

```php
// ext_tables.php
$GLOBALS['TYPO3_CONF_VARS']['FluidTYPO3.FluidcontentCore']['variants']['text'][] = 'myextensionkey';
$GLOBALS['TYPO3_CONF_VARS']['FluidTYPO3.FluidcontentCore']['variants']['textpic'][] = 'myextensionkey';
```

Then create the template files:

```xml
<!-- EXT:myextensionkey/Resource/Private/Templates/Content/Text.html -->
The text: {record.bodytext}
```

```xml
<!-- EXT:myextensionkey/Resource/Private/Templates/Content/Textpic.html -->
The text: {record.bodytext}
<!-- some custom rendering of images -->
```

This would make a total of two variants of the `text` and `textpic` `CType` content elements: The always-present, first option
called "Standard" which means "no thanks, just the default type in whichever template path the site TS has configured, please",
and your newly added variant - which will be identified by the extension key to which it belongs.

Selecting a variant when editing either `text` or `textpic` elements from then on, will make FluidcontentCore render the variant
template from your extension instead of the one that exists in the default template location configured in TypoScript.

> Note: changing the TypoScript that sets templateRootPath etc. for `myextensionkey` in this example, will make FluidcontentCore
> look in that other location for the template files belonging to your variant!

You cannot choose custom template file names for your variants, nor a custom location - they must be in the `Content` template
folder and must be named in UpperCamelCase according to the `CType` they cover: `Text.html`, `Textpic.html`, `Uploads.html` etc.

> Note: the template files **must exist or your variant will be ignored!**

### Concept: Versions

> Use case: you already provide a "variant" of an element but your should now to provide more "versions" of the element, with one
> of two purposes: 1) making it possible to test new element designs in a single content element and/or 2) making logical versions
> of your "variant" that can be selected only when your "variant" type is selected.

Since this concept only applies to you when you use variants, you must first register a variant for each type of element you need:

```php
// ext_tables.php
$GLOBALS['TYPO3_CONF_VARS']['FluidTYPO3.FluidcontentCore']['variants']['text'][] = 'myextensionkey';
```

```xml
<!-- EXT:myextensionkey/Resource/Private/Templates/Content/Text.html -->
The basic version: {record.bodytext}
```

Then, to provide more versions of the "text" content element:

```xml
<!-- EXT:myextensionkey/Resource/Private/Templates/Content/Text/Truncated.html -->
The shortened version: {record.bodytext -> f:format.crop(maxCharacters: 100)}
```

```xml
<!-- EXT:myextensionkey/Resource/Private/Templates/Content/Text/Raw.html -->
The raw version: {record.bodytext -> f:format.raw()}
```

And so on. These are of course very basic examples - the point of this is not to document every possible use case, the point is
to inspire you to use these concepts to reach your specific goal. Remember: you can even place Flux form fields into Partial
templates and simply render those from all versions, to make versions share one or more Flux form fields. The same is possible
with Flux form sheets etc.

> Note: although the same behavior as "versions" enables can in most cases also be achieved through Flux form settings like a
> selector to change between rendering cropped, raw, html-formatted etc. texts and then using conditions in the template itself -
> however, using "versions" enables you to save a lot on template complexity and allows including legacy elements which only get
> touched when that specific version is selected. Much like the different versions of the static TypoScript `css_styled_content`
> uses to provide easy compatibility.

## Built-in content element types

* Header.html
* Text.html
* Image.html
* Textpic.html
* Bullets.html
* Uploads.html
* Table.html
* Media.html
* Mailform.html
* Search.html
* Menu.html
* Shortcut.html
* Div.html
* Html.html (yep, html-dot-html, like that...)
* Default.html

## Plans for future improvements

1. Finish the basic set of included templates to make a fallback-quality rendering
2. Implement any shared fields every content element should use
3. Introduce an asset configuration integration (possibly leveraging LESS/SASS to grab Flux Form values to use in compiled CSS)
4. Try to take over the world. Same as every other night, Pinky.


[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/FluidTYPO3/fluidcontent_core/trend.png)](https://bitdeli.com/free "Bitdeli Badge")

