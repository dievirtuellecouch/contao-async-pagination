# Changelog

## 5.7.5

- Scroll pagination to the filter controls whenever the wrapper has a filter,
  including when no category is selected. Without a filter, scroll to the list.
- Keep the existing opt-in, reduced-motion and view-transition behavior.
  Extend regression coverage for pagination with and without filters.
  No database migration needed.

## 5.7.4

- Scroll to the filter controls after filter selection or reset, keeping them
  visible above the reloaded content. Pagination continues to scroll to the list.
- Keep the existing opt-in checkbox and reduced-motion/transition behavior;
  fall back to the list if a custom wrapper has no filter container.
- Add regression coverage for select and link filters, reset, filtered pagination,
  disabled scrolling and missing filter containers. No database migration needed.

## 5.7.3

- Add an opt-in checkbox to scroll to the start of the reloaded list after
  pagination or filter changes. Wait for view transitions and respect reduced motion.
- Preserve the preview front controller and installation base path in generated
  pagination endpoints and source-page matching.
- Delegate pagination clicks on the persistent wrapper and always reset loading
  state after errors.
- Extend page-context and scrolling regression coverage.

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
