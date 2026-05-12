# ps_mbo - AI Developer Context

## Role in the Ecosystem

`ps_mbo` is a PrestaShop back-office module. Its two core responsibilities:

1. **Addons catalog UI**: injects a Cross Domain Component (CDC) served by
   `mbo.prestashop.com` that renders the full Addons marketplace inside the PS
   back-office (module catalog, theme catalog, recommended modules).
2. **Remote module management**: exposes a signed HTTP API so that the MBO
   back-end (`mbo.prestashop.com`) can remotely trigger module
   install/upgrade/uninstall actions on a merchant shop.

Ecosystem map: `.ai-tools/ECOSYSTEM_CONTEXT.md`

Key upstream/downstream:
- `mbo.prestashop.com` -> `ps_mbo`: serves the CDC JS bundle and calls the
  remote API endpoint to manage modules
- `ps_mbo` -> Distribution API (`https://mbo-api.prestashop.com`): fetches
  module catalogue data (authenticated and anonymous)
- `ps_mbo` -> Addons API (`https://api-addons.prestashop.com`): downloads
  module zip files for install/upgrade
- `ps_mbo` requires `ps_accounts` to be installed; the Accounts JWT token is
  forwarded to the Distribution API for authenticated calls

## Stack

- PHP 8.1+, `declare(strict_types=1)` everywhere
- PrestaShop 8.x / 9.x (entry guard: `version_compare(_PS_VERSION_, '8.0.2', '<')`)
- Symfony 6.4 (DI container, Workflow, String components)
- Guzzle HTTP (via PSR-18 interfaces) for outbound API calls
- Smarty (hook templates) + Twig (Symfony controller views)
- PHPUnit 9.x, PHP-CS-Fixer 3.x, PHPStan 1.x

## Architecture

### Main class composition

`ps_mbo.php` extends PrestaShop's `Module` and composes all behaviour via traits:

```
ps_mbo
  UseHooks              (src/Traits/UseHooks.php)
    Hooks\UseDashboardZoneOne
    Hooks\UseDashboardZoneThree
    Hooks\UseDisplayAdminThemesListAfter
    Hooks\UseDisplayDashboardTop
    Hooks\UseActionBeforeInstallModule
    Hooks\UseActionBeforeUpgradeModule
    Hooks\UseActionGetAlternativeSearchPanels
    Hooks\UseActionListModules
    Hooks\UseDisplayEmptyModuleCategoryExtraMessage
  HaveTabs              (src/Traits/HaveTabs.php)
  HaveConfigurationPage (src/Traits/HaveConfigurationPage.php) — dev only
  HaveCdcComponent      (src/Traits/HaveCdcComponent.php)
```

Each PS hook lives in its own file under `src/Traits/Hooks/`. `UseHooks::bootHooks()`
calls `boot{TraitName}()` on each hook trait at module init, allowing per-hook
service initialization without a monolithic `__construct`.

### CDC (Cross Domain Component)

The Addons catalog UI is **not** built in this repo. It is a compiled JS bundle
(`mbo-cdc.umd.js`) hosted on the MBO CDN (`MBO_CDC_URL` env var, default:
`https://assets.prestashop3.com/dst/mbo/v1/mbo-cdc.umd.js`). The source lives
in the `mbo.prestashop.com` repo.

`ps_mbo` injects this bundle into the PS back-office via hook templates
(Smarty `.tpl` files in `views/templates/hook/`). `HaveCdcComponent::smartyDisplayTpl()`
is the shared rendering method; it assigns a Symfony-generated error URL
(`admin_mbo_module_cdc_error` route) before delegating to Smarty.

Hooks that inject CDC content:
- `hookDashboardZoneOne`: module catalog page (main zone)
- `hookDashboardZoneThree`: module catalog page (secondary zone)
- `hookDisplayAdminThemesListAfter`: theme catalog

### Remote API endpoint

`controllers/admin/apiPsMbo.php` is a legacy-style PS controller that the MBO
back-end calls to manage modules on the merchant's shop. Security relies on
HMAC signature verification (action + module + admin token + action UUID,
signed with a public key retrieved from the Distribution API). The
`apiSecurityPsMbo.php` controller handles key rotation.

`src/Api/` contains:
- `Controller/AbstractAdminApiController.php`: base class with signature
  validation, CORS handling, JSON response helpers
- `Service/ModuleTransitionExecutor.php`: executes install/upgrade/uninstall
  transitions using Symfony Workflow
- `Service/ConfigApplyExecutor.php`: applies remote config changes
- `Service/Factory.php`: resolves which executor to invoke from `?service=` param

### Module lifecycle (install/upgrade from Addons)

`hookActionBeforeInstallModule` / `hookActionBeforeUpgradeModule` intercept PS
module manager operations and download the zip from Addons before the native
install runs:

```
hook -> ActionsManager::install(moduleId)
          -> FilesManager (download zip from Addons API)
          -> native PS install picks up the local zip
```

Symfony Workflow (`symfony/workflow`) models the allowed transitions between
module states (installed, enabled, upgraded, etc.).

### Module collection / catalogue

`src/Module/` provides the data layer for the hook that enriches the module list:
- `Repository`: queries the local PS module database
- `CollectionFactory` + `Collection`: builds typed module lists
- `FiltersFactory` + `Filters`: filtering by status/category
- `ModuleBuilder`: constructs `Module` VO from raw PS data
- `Module` VO wraps the legacy `\Module` instance with a `ParameterBag`

### Distribution API clients

Two clients in `src/Distribution/`, both extending `BaseClient` (Guzzle + PSR-18):

| Class | Auth | Use |
|-------|------|-----|
| `Client` | anonymous | public key rotation, event tracking |
| `ConnectedClient` | Accounts JWT + Addons credentials | authenticated module catalogue |

The `accounts_token` query parameter carries the PS Accounts JWT for authenticated
Distribution API calls. Shops without a linked Accounts are served by `Client`.

### Addons API client

`src/Addons/ApiClient.php` handles zip downloads from
`https://api-addons.prestashop.com`. Used by `ActionsManager` when resolving
module sources before install/upgrade.

### Symfony routes

All routes prefixed under `/mbo/`:

| Prefix | Controller | Purpose |
|--------|-----------|---------|
| `/mbo/modules/catalog` | `ModuleCatalogController` | module catalog page/AJAX |
| `/mbo/modules/recommended` | `ModuleRecommendedController` | recommended modules panel |
| `/mbo/themes/catalog` | `ThemeCatalogController` | theme catalog |
| `/mbo/addons` | `AddonsController` | Addons-specific routes |

### Service container

Symfony DI defined in `config/services/` (YAML files + one `addons.php` for
Addons-related services). `src/DependencyInjection/ContainerProvider.php`
exposes the Symfony container to legacy PS code via `$this->get(ServiceClass::class)`.
All services must be `public: true` for legacy compatibility.

### Views

- `views/templates/hook/*.tpl` - Smarty: CDC injection, recommended modules,
  empty category messages
- `views/templates/admin/` - Twig: error page, AJAX layout (used by Symfony
  controllers)

## Environment Variables

Defined in `.env` (sourced from GCP Secret Manager in CI); see `.env.dist` for
defaults:

| Variable | Default | Description |
|----------|---------|-------------|
| `MBO_CDC_URL` | `https://assets.prestashop3.com/dst/mbo/v1/mbo-cdc.umd.js` | CDC JS bundle URL |
| `DISTRIBUTION_API_URL` | `https://mbo-api.prestashop.com` | MBO distribution API |
| `ADDONS_API_URL` | `https://api-addons.prestashop.com` | Addons zip download API |
| `SENTRY_CREDENTIALS` | (DSN) | Error monitoring |
| `SENTRY_ENVIRONMENT` | `production` | Sentry env tag |

## Key Commands

```bash
composer install          # install all dependencies (dev included)
composer install --no-dev -o  # production install
composer test             # run PHPUnit
composer lint             # php-cs-fixer (dry-run diff)
make build-zip            # local zip without dev artifacts
```

## Conventions

- PSR-12, `declare(strict_types=1)` at top of every file
- One hook = one trait in `src/Traits/Hooks/`; boot logic in `boot{TraitName}()`
- All services `public: true` in DI config (PS legacy DI constraint)
- No class overrides; hooks only
- `HaveConfigurationPage.php` is deleted from production zips by CI and its
  `use` statement is stripped from `ps_mbo.php` via `sed`; do not put
  production-facing logic in that file

## CI / Artifacts

| Workflow | Trigger | Purpose |
|----------|---------|---------|
| `build-release.yml` | push/PR/release | builds composer, creates zip, uploads to GCS (preprod) or GitHub release (prod) |
| `php.yml` | PR | php-linter, php-cs-fixer, phpstan, phpunit |
| `translations.yml` | push to master | Crowdin sync |
| `install-mydemoshop.yml` | manual | test install on a live demo shop |

AI context files (`.ai-tools/`, `.claude/`, `CLAUDE.md`) are excluded from
all zip artifacts by CI and `make build-zip`.

## Points of Attention

- The CDC URL is injected at runtime from the `.env` file; if the CDC JS
  fails to load, `admin_mbo_module_cdc_error` renders a graceful fallback page
- `ConnectedClient` requires a valid PS Accounts token; anonymous shops silently
  fall back to `Client` (limited catalogue, no purchase history)
- PHPStan config is in `tests/phpstan/`; baseline and custom rules live there
- The HMAC signature in `apiPsMbo.php` includes `admin_token` (a PS back-office
  token, not an Accounts token): this is separate from the Accounts JWT used for
  the Distribution API
