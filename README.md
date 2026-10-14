# to-faq

Tour Operator FAQ adds FAQs to Tour Operator sites, built on the Yoast FAQ block (`yoast/faq-block`).

## Features

### Collapsible FAQ accordion

The Yoast FAQ block becomes an accessible, collapsible accordion.

- Questions become keyboard-operable toggles (`role="button"`, `aria-expanded`, `aria-controls`, Enter and Space).
- Collapsed answers are `inert`, so their links can't receive focus.
- Without JavaScript the FAQ stays fully expanded. Motion is disabled under `prefers-reduced-motion`.
- The script and stylesheet only load on pages that render the FAQ block.

### Post FAQs

Requires Yoast SEO and Secure Custom Fields.

- Tours, accommodation and destinations get a repeatable **FAQs** field group (question, answer, optional image). Change the post types with the `lsx_to_faq_post_types` filter.
- The **Post FAQs** variation of the Yoast FAQ block shows those fields on the front end. It renders nothing when the post has no FAQs.
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
```

| Source | Build output |
| --- | --- |
| `src/js/faq-accordion.js` | `build/js/faq-accordion.js` |
| `src/js/faq-fields.js` | `build/js/faq-fields.js` |
| `src/css/faq-accordion.scss` | `build/css/faq-accordion.css` |

Add new entry points to `webpack.config.cjs`. The `build/` folder is committed.
