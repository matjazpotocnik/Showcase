# UIkit Component Catalogue

Originally copied from ProcessWire's `wire/modules/AdminTheme/AdminThemeUikit/uikit-pw/tests/`
(UIkit 3.23.10-era catalogue). The 27 HTML pages supplied in `../uikit-tests-new/`
were merged into this catalogue on 2026-10-08; 11 existing pages changed.
These incoming pages target UIkit 3.25.19, matching the installed runtime.
All 86 catalogue pages are retained, including the older Overview and pages
absent from the incoming subset. This is a mixed-version catalogue, not a full
upstream 3.25.19 suite. This is UIkit 3, not UIkit 5.
The upstream UIkit MIT license is preserved in `LICENSE.md`.

The incoming HTML files are copied unchanged, including the supplied video URL
fixes in `base.html` and `cover.html`. Existing images, unused runner files and
support assets remain intact. The additional
`../src/images/` directory preserves the relative SVG references in the fixtures.
For partial updates, replace only supplied HTML pages and retain missing pages,
this README and the license. Do not upgrade core CSS or JavaScript for fixtures.

Showcase fetches each document once and renders two isolated previews. The admin
preview reuses the active admin's stylesheet links, inline styles and root class.
The stock preview loads only stock `uikit.css`, not admin CSS or tokens.
Neither preview loads the upstream `css/index.css` presentation stylesheet.
Both reuse the installed core-compatible UIkit runtime and preserve fixture
head styles and component-specific scripts, excluding the upstream test switcher.

Component, light/dark, inverse-context and RTL controls update both previews.
Stock UIkit has no admin dark palette: dark mode changes its canvas and native
color scheme, while component colors remain stock unless an inverse context is
selected. A shared scrollbar synchronizes relative vertical position; scrolling
inside either preview also drives the other. Different layouts can place the
same component at different relative positions. Panes stack on narrow screens.

Inspect mode highlights hovered elements and displays their tag, ID, classes,
computed text color and background. Clicking holds the selection and suppresses
the component action; toggle Inspect off and on to resume hovering. The admin
canvas uses the theme's background token rather than a fixed fixture color.

Run visual checks through Setup > Showcase > UIkit components. Opening the raw
fixtures directly does not reproduce the live admin theme.
