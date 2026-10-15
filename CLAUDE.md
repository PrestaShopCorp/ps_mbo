# ps_mbo - AI Developer Context

## Role in the Ecosystem

`ps_mbo` is a PrestaShop back-office module. Its two core responsibilities:

1. **Addons catalog UI**: injects a Cross Domain Component (CDC) served by
   `mbo.prestashop.com` that renders the full Addons marketplace inside the PS
   back-office (module catalog, theme catalog, recommended modules).
2. **Addons bridge**: acts as the intermediary between the shop and the Addons
   platform for everything module-lifecycle related: intercepting install/upgrade
   requests to download zips directly from Addons (`AddonsUrlSourceHandler` +
   `hookActionBeforeInstallModule` / `hookActionBeforeUpgradeModule`), and
   enriching module lists with catalogue metadata from the Distribution API.

Ecosystem map: `.ai-tools/ECOSYSTEM_CONTEXT.md`

Key upstream/downstream:
- `mbo.prestashop.com` -> `ps_mbo`: serves the CDC JS bundle
- `ps_mbo` -> Distribution API (`https://mbo-api.prestashop.com`): fetches
  module catalogue data (authenticated and anonymous)
- `ps_mbo` -> Addons API (`https://api-addons.prestashop.com`): downloads
  module zip files for install/upgrade
- `ps_mbo` requires `ps_accounts` to be installed; the Accounts JWT token is
  forwarded to the Distribution API for authenticated calls

## Architecture

### CDC (Cross Domain Component)

The Addons catalog UI is **not** built in this repo. It is a compiled JS bundle
(`mbo-cdc.umd.js`) hosted on the MBO CDN (`MBO_CDC_URL` env var), injected via
the Smarty hook templates in `views/templates/hook/`.

### Module lifecycle (install/upgrade from Addons)

`ps_mbo` intercepts the native PS module manager install and upgrade flows to
download zips from Addons transparently:

Two distinct paths depending on whether a `$source` is provided to `ModuleManager`:

**Path A - source provided (e.g. Addons URL):**
```
ModuleManager::install($name, $source)
  -> sourceFactory->getHandler($source)   // PS Core
       -> AddonsUrlSourceHandler::canHandle($source) == true
            -> AddonsUrlSourceRetriever::get($source)  (downloads zip)
            -> ZipSourceHandler::handle($localZipPath)
  hookActionBeforeInstallModule fires but does nothing (source already resolved)
```

**Path B - no source (module name only):**
```
ModuleManager::install($name, source=null)
  -> no SourceHandler invoked
  -> hookActionBeforeInstallModule fires
       -> ActionsManager::install(moduleId)
            -> Addons API download by module ID
            -> native PS installer picks up the local zip
```

`AddonsUrlSourceHandler` (`src/Module/SourceHandler/`) implements PS Core's
`SourceHandlerInterface` and is registered in the DI container so the source
factory picks it up automatically when the caller provides an Addons URL.

The hook is therefore a fallback: it covers call sites that don't pass a source.

## Environment

`.env` is sourced from GCP Secret Manager in CI; see `.env.dist` for defaults.

## Conventions

- PSR-12, `declare(strict_types=1)` at top of every file
- One hook = one trait in `src/Traits/Hooks/`; boot logic in `boot{TraitName}()`
- All services `public: true` in DI config (PS legacy DI constraint)
- No class overrides; hooks only
- `HaveConfigurationPage.php` is deleted from production zips by CI and its
  `use` statement is stripped from `ps_mbo.php` via `sed`; do not put
  production-facing logic in that file
- AI context files (`.ai-tools/`, `.claude/`, `CLAUDE.md`) are excluded from
  all zip artifacts by CI and `make build-zip`

## Points of Attention

- The CDC URL is injected at runtime from the `.env` file; if the CDC JS
  fails to load, `admin_mbo_module_cdc_error` renders a graceful fallback page
- `ConnectedClient` requires a valid PS Accounts token; anonymous shops silently
  fall back to `Client`
- PHPStan config is in `tests/phpstan/`; baseline and custom rules live there
- `src/Api/Security/AdminAuthenticationProvider.php` is the only survivor of the former v8 inbound API stack; it provides `getMboJWT()` and `getMboToken()` for outbound Distribution/Addons API auth only
