# Changelog

All notable changes to this plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added
- Added a collapsible accordion for the Yoast FAQ block, ported from Tour Operator ([tour-operator#1389](https://github.com/lightspeedwp/tour-operator/pull/1389), TO-138). Each question is a keyboard-operable toggle (`role="button"`, `aria-expanded`, `aria-controls`, Enter and Space). Collapsed answers are `inert`, so their links can't receive focus. Without JavaScript the FAQ stays fully expanded, and motion is disabled under `prefers-reduced-motion`. The script and stylesheet only load on pages that render the FAQ block. The FAQ template part area is still registered by Tour Operator core.
- Added the `@wordpress/scripts` webpack build process (ported from ls-plugin), compiling `src/` to `build/js` and `build/css`.
