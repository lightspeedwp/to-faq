# to-faq

Tour Operator FAQ turns the Yoast FAQ block (`yoast/faq-block`) into an accessible, collapsible accordion.

- Questions become keyboard-operable toggles (`role="button"`, `aria-expanded`, `aria-controls`, Enter and Space).
- Collapsed answers are `inert`, so their links can't receive focus.
- Without JavaScript the FAQ stays fully expanded. Motion is disabled under `prefers-reduced-motion`.
- The script and stylesheet only load on pages that render the FAQ block.

The FAQ template part area is registered by the Tour Operator core plugin, not here.

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
| `src/css/faq-accordion.scss` | `build/css/faq-accordion.css` |

Add new entry points to `webpack.config.cjs`. The `build/` folder is committed.
