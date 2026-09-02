# Ideas

Only small, additive features are listed here. Refactors and package-wide redesigns are intentionally excluded.

## 1. First-visit locale detection

Use the browser's `Accept-Language` header as a fallback when neither the session nor the user preference contains a locale, while limiting the result to the locales enabled by the plugin.

## 2. Per-panel locale preferences

Allow applications with multiple Filament panels to store a separate locale for each panel instead of sharing one global preference.

## 3. Document language and direction

Expose the current locale's `lang` and `dir` values so panels can set the correct HTML attributes, including right-to-left languages.
