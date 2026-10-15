# Changelog

All notable changes to this plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added
- Added Yoast FAQ blocks from templates and template parts to Yoast SEO's FAQ schema. Yoast only reads FAQ blocks written in a post's own content, so FAQ sections placed through a template part, pattern, synced pattern or the block template rendered without structured data. They are now passed into Yoast's schema context on singular pages, skipping questions already included. Turn it off with the `lsx_to_faq_add_template_schema` filter. The template walker the "TO FAQs" schema already used moved to a shared `Block_References` class. See [#4](https://github.com/lightspeedwp/to-faq/pull/4).
- Added an **FAQs** meta box to posts and the Tour Operator post types, and a **TO FAQs** variation of the Yoast FAQ block that renders it on the front end. The meta box is a repeatable, sortable group of a question and a WYSIWYG answer (like the itinerary description), so answers support basic formatting and images through Add Media. It is registered with the CMB2 library bundled with Tour Operator, the same way as the Tour Operator meta boxes. The block renders nothing when a post has no FAQs, and the collapsible accordion applies to it. The questions are passed into Yoast SEO's FAQ schema, so Yoast still outputs the structured data. Other plugins can change the post types with the `lsx_to_faq_post_types` filter, and the output with `lsx_to_faq_questions` and `lsx_to_faq_add_schema`. Requires Tour Operator and Yoast SEO, which the plugin declares with the `Requires Plugins` header. The minimum versions are raised to WordPress 6.7 and PHP 8.2 to match Tour Operator, so WordPress enforces that header. See [#3](https://github.com/lightspeedwp/to-faq/pull/3).
- Added `languages/to-faq.pot`. `npm run i18n` now generates it with `wp i18n make-pot`, like Tour Operator core. See [#3](https://github.com/lightspeedwp/to-faq/pull/3).
- Added a `to-faq/faq` pattern built on the Yoast FAQ block, and a `faq` template part that calls it, ported from ls-plugin. Both are only registered while Yoast SEO is active. The part is placed in the `faq` template part area that Tour Operator core registers. More template parts can be added with the `lsx_to_faq_template_parts` filter.
- Added a collapsible accordion for the Yoast FAQ block, ported from Tour Operator ([tour-operator#1389](https://github.com/lightspeedwp/tour-operator/pull/1389), TO-138). Each question is a keyboard-operable toggle (`role="button"`, `aria-expanded`, `aria-controls`, Enter and Space). Collapsed answers are `inert`, so their links can't receive focus. Without JavaScript the FAQ stays fully expanded, and motion is disabled under `prefers-reduced-motion`. The script and stylesheet only load on pages that render the FAQ block. The FAQ template part area is still registered by Tour Operator core.
- Added the `@wordpress/scripts` webpack build process (ported from ls-plugin), compiling `src/` to `build/js` and `build/css`.
