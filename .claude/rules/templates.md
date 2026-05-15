---
paths:
  - "views/templates/**/*.tpl"
  - "views/templates/**/*.html.twig"
---

# Template conventions

## Escaping

- **Smarty**: always escape user-facing output with `{$var|escape:'html':'UTF-8'}` or `{$var|escape}`
- **Twig**: use `{{ var | e }}` or `{{ var }}` (auto-escape is on); disable with `| raw` only for trusted HTML built server-side

## No logic in templates

Templates must only display data already prepared by the controller or hook method. No service calls, no `Db` queries, no business logic. Move any conditional logic to the PHP side and pass a boolean flag.

## CDC templates (`.tpl` in `hook/`)

- The CDC JS bundle URL comes from the `$mbo_cdc_url` Smarty variable, never hardcoded
- Do not reference `assets.prestashop3.com` or any CDN URL directly
- The error fallback URL comes from the `$mbo_error_url` variable set by `HaveCdcComponent::smartyDisplayTpl()`

## Twig admin templates

- Extend `@PrestaShop/Admin/layout.html.twig` for full-page views, or `layout-ajax.html.twig` for AJAX-loaded panels
- Use `{{ path('route_name') }}` for internal URLs, never string concatenation
- Translation strings: `{{ 'key'|trans({}, 'Admin.Modules.Feature') }}`

## Asset loading

- JS/CSS assets are registered via the controller's `Media` calls or Smarty `{addjs}`/`{addcss}`, not inline `<script>` or `<style>` tags in templates
