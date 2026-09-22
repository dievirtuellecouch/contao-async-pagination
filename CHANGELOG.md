# Changelog

## 5.7.2

- Restore the source page and locale when rendering Ajax modules and content.
- Initialize Contao's default translations for legacy frontend modules.
- Fix debug-mode failures in news category filtering and news pagination caused
  by the missing page context and language data.
- Preserve existing endpoints, JSON responses, templates and configuration;
  support older clients using same-origin Referer or the published site root.
- Run page access and maintenance checks with the resolved page context.
- Separate cached legacy responses by context headers and keep protected page
  responses private.
- Refresh both bundled JavaScript asset versions and add context regression tests.
