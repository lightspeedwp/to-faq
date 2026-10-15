# to-faq end-to-end tests

Playwright tests for the plugin's FAQ schema. They run against a WordPress site with this plugin, Tour Operator and Yoast SEO active.

## Run

```bash
npm install
npx playwright install chromium   # or set PW_CHANNEL=chrome to use an installed Chrome
WP_BASE_URL=http://asc.local npm run test:e2e
```

## Fixture pages

The defaults are the ASC site's pages. Override them to run on another site, such as Southern Destinations.

| Variable | Default | Needs |
| --- | --- | --- |
| `FAQ_IN_CONTENT_PART_URL` | `/contact/` | A template part with a Yoast FAQ block, inserted in the page content. |
| `FAQ_IN_TEMPLATE_URL` | `/faq/` | A page whose block template includes Yoast FAQ blocks (through template parts). |
| `TO_FAQ_POST_URL` | `/south-africa/` | A post with FAQ fields, whose template contains the "TO FAQs" block. |
| `TO_FAQ_EMPTY_POST_URL` | `/botswana/` | A post on the same template, without FAQ fields. |
| `FAQ_ARCHIVE_URL` | `/team/` | An archive whose template shows a Yoast FAQ block. |

## Specs

| Spec | Covers |
| --- | --- |
| `template-faq-schema.spec.ts` | `Template_Faq_Schema`: Yoast FAQ blocks from a template part in the content, and from the block template, produce `FAQPage` schema listing each visible question once. The "TO FAQs" schema still matches after the `Block_References` refactor. A post without FAQ fields gets no FAQ schema, and archives get none. |
