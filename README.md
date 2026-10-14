# to-faq

Tour Operator FAQ adds FAQs to Tour Operator sites, built on the Yoast FAQ block (`yoast/faq-block`).

## Features

### Collapsible FAQ accordion

The Yoast FAQ block becomes an accessible, collapsible accordion.

- Questions become keyboard-operable toggles (`role="button"`, `aria-expanded`, `aria-controls`, Enter and Space).
- Collapsed answers are `inert`, so their links can't receive focus.
- Without JavaScript the FAQ stays fully expanded. Motion is disabled under `prefers-reduced-motion`.
- The script and stylesheet only load on pages that render the FAQ block.

### TO FAQs

Requires Yoast SEO and Tour Operator. The meta box uses the CMB2 library bundled with Tour Operator.

- Posts and the Tour Operator post types (from `lsx_to_get_post_types()`) get an **FAQs** meta box: a repeatable, sortable group of a question and an answer. The answer is a WYSIWYG editor, like the itinerary description, so it supports basic formatting and images through Add Media. Add or remove post types with the `lsx_to_faq_post_types` filter.
- The **TO FAQs** variation of the Yoast FAQ block shows those fields on the front end. It renders nothing when the post has no FAQs.
- The questions are passed into Yoast SEO's FAQ schema, so Yoast outputs the structured data. This only happens when the block is on the page (in the content, a template, or a template part). Override it with `lsx_to_faq_add_schema`.

### FAQ pattern and template part

Requires Yoast SEO.

- A `to-faq/faq` pattern: a heading followed by a Yoast FAQ block.
- A `faq` template part that calls the pattern. It is created for the active theme if it doesn't already exist.

The `faq` template part area is registered by the Tour Operator core plugin, not here.

## Development

```bash
npm ci
npm run build   # compile src/ to build/
npm start       # watch mode
npm run lint
npm run i18n    # regenerate languages/to-faq.pot
```

`npm run i18n` runs `wp i18n make-pot`, so it needs [WP-CLI](https://wp-cli.org/) with the `wp` command on your `PATH`.

| Source | Build output |
| --- | --- |
| `src/js/faq-accordion.js` | `build/js/faq-accordion.js` |
| `src/js/faq-fields.js` | `build/js/faq-fields.js` |
| `src/css/faq-accordion.scss` | `build/css/faq-accordion.css` |

Add new entry points to `webpack.config.cjs`. The `build/` folder is committed.
