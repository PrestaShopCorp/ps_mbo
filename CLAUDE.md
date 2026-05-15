# ps_mbo 4.x - AI Developer Context

## Role in the Ecosystem

`ps_mbo` is a PrestaShop back-office module. Its core responsibilities on the 4.x branch:

1. **Addons catalog UI**: injects a Cross Domain Component (CDC) served by
   `mbo.prestashop.com` that renders the full Addons marketplace inside the PS
   back-office (module catalog, theme catalog, recommended modules).
2. **Addons bridge**: acts as the intermediary between the shop and the Addons
   platform for module lifecycle operations: install/upgrade zip downloads from
   Addons, and enriching module lists with catalogue metadata from the Distribution API.
3. **Remote management API**: registers the shop with `mbo.prestashop.com` and
   exposes HMAC-signed endpoints that allow the MBO back-end to trigger remote
   actions (module installs, security updates) on the shop. This involves creating
   and maintaining a persistent MBO admin user on the shop.

Ecosystem map: `.ai-tools/ECOSYSTEM_CONTEXT.md`

Key upstream/downstream:
- `mbo.prestashop.com` -> `ps_mbo`: serves the CDC JS bundle AND calls HMAC-signed
  admin endpoints for remote module management
- `ps_mbo` -> Distribution API (`https://mbo-api.prestashop.com`): fetches
  module catalogue data (authenticated and anonymous)
- `ps_mbo` -> Addons API (`https://api-addons.prestashop.com`): downloads
  module zip files for install/upgrade
- `ps_mbo` requires `ps_accounts` to be installed; the Accounts JWT token is
  forwarded to the Distribution API for authenticated calls

## Stack

- PHP >=7.2.5, `declare(strict_types=1)` everywhere
- PrestaShop 8.x (entry guard: `version_compare(_PS_VERSION_, '8.0.2', '<')`)
- Symfony 5.x (DI container, Workflow, String components)
- Guzzle HTTP (via PSR-18 interfaces) for outbound API calls
- firebase/php-jwt ^6.3 for JWT handling
- Smarty (hook templates) + Twig (Symfony controller views)
- PHPUnit 8.x/9.x, PHP-CS-Fixer 3.x, PHPStan 1.x

## Architecture

### Main class composition

`ps_mbo.php` extends PrestaShop's `Module` and composes all behaviour via traits:

```
ps_mbo
  UseHooks                           (src/Traits/UseHooks.php)
    Hooks\UseDashboardZoneOne
    Hooks\UseDashboardZoneTwo
    Hooks\UseDashboardZoneThree
    Hooks\UseDisplayAdminThemesListAfter
    Hooks\UseDisplayDashboardTop
    Hooks\UseDisplayAdminAfterHeader     <- connection toolbar (4.x only)
    Hooks\UseActionAdminControllerSetMedia  <- CSS/JS for toolbar (4.x only)
    Hooks\UseActionGetAdminToolbarButtons   <- MBO toolbar buttons (4.x only)
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

### Remote Management API (4.x active feature)

`ps_mbo` registers the shop with the MBO back-end (`mbo.prestashop.com`) and
exposes a remote action API. This allows the MBO team to trigger module operations
(security patches, forced upgrades) on the shop without merchant intervention.

**How it works:**
1. During install/register, `ps_mbo` creates a dedicated MBO admin employee on the
   shop and keeps a session alive for that employee.
2. `mbo.prestashop.com` calls the shop's back-office URL directly (e.g.,
   `https://shop.example.com/admin123/`), authenticated as this MBO employee.
3. Requests are protected by HMAC signature (`src/Api/Security/`).
4. `apiPsMbo.php` dispatches actions via `ModuleTransitionExecutor`; `apiSecurityPsMbo.php`
   handles signature verification.

**Known limitations (by design on 4.x):**
- Calls fail when hosting/Cloudflare blocks back-office direct access
- Network restrictions (closed firewalls, VPN) can prevent MBO from reaching the shop
- This mechanism was removed in v5/PS9 in favour of simpler shop-side polling

Files:
- `controllers/admin/apiPsMbo.php` and `apiSecurityPsMbo.php`: legacy-style PS controllers
- `src/Api/`: HMAC verifier, `AbstractAdminApiController`, `ModuleTransitionExecutor`, `ConfigApplyExecutor`

### Tab System

The 4.x branch registers PS back-office tabs via `src/Tab/`:

- `Tab` / `TabInterface`: value object for a single tab entry
- `TabCollection` / `TabCollectionInterface`: typed list of tabs
- `TabCollectionFactory` / `TabCollectionFactoryInterface`: builds the collection from config
- `TabCollectionDecoderXml`: parses tab config from XML
- `TabCollectionProvider` / `TabCollectionProviderInterface`: provides tabs to `HaveTabs`

`HaveTabs::getTabs()` returns this collection so PrestaShop registers/unregisters
tabs on install/uninstall.

### CDC (Cross Domain Component)

The Addons catalog UI is **not** built in this repo. It is a compiled JS bundle
(`mbo-cdc.umd.js`) hosted on the MBO CDN (`MBO_CDC_URL` env var, default:
`https://assets.prestashop3.com/dst/mbo/v1/mbo-cdc.umd.js`).

`ps_mbo` injects this bundle into the PS back-office via hook templates
(Smarty `.tpl` files in `views/templates/hook/`). `HaveCdcComponent::smartyDisplayTpl()`
is the shared rendering method.

Hooks that inject CDC content:
- `hookDashboardZoneOne`: module catalog page (main zone)
- `hookDashboardZoneTwo`: module catalog page (secondary zone)
- `hookDashboardZoneThree`: module catalog page (third zone)
- `hookDisplayAdminThemesListAfter`: theme catalog

### Connection Toolbar (4.x only)

`UseDisplayAdminAfterHeader` renders a toolbar in the PS back-office header that:
- Shows MBO connection status (registered / not registered)
- Provides a CTA to register the shop if not done
- Displays a "MBO employee" explanation banner when the MBO admin user is active

Related assets: `views/css/connection-toolbar.css`, `views/js/connection-toolbar.js`,
`views/templates/hook/configure-toolbar.tpl`,
`views/templates/hook/twig/explanation_mbo_employee.html.twig`,
`views/templates/hook/twig/failed-api-user.html.twig`.

### Module lifecycle (install/upgrade from Addons)

`ps_mbo` intercepts the native PS module manager install and upgrade flows to
download zips from Addons transparently:

Two distinct paths depending on whether a `$source` is provided to `ModuleManager`:

**Path A - source provided (e.g. Addons URL):**
```
ModuleManager::install($name, $source)
  -> sourceFactory->getHandler($source)
       -> AddonsUrlSourceHandler::canHandle($source) == true
            -> AddonsUrlSourceRetriever::get($source)
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

### Addons API client

`src/Addons/ApiClient.php` handles zip downloads from
`https://api-addons.prestashop.com`. Used by `ActionsManager` when resolving
module sources before install/upgrade.

### Symfony routes

All routes prefixed under `/mbo/`:

| Prefix | Controller | Purpose |
|--------|-----------|---------|
| `/mbo/modules/catalog` | `ModuleCatalogController` | module catalog |
| `/mbo/modules/recommended` | `ModuleRecommendedController` | recommended modules panel |
| `/mbo/modules/selection` | `ModuleSelectionController` | module selection (4.x only) |
| `/mbo/themes/catalog` | `ThemeCatalogController` | theme catalog |
| `/mbo/addons` | `AddonsController` | Addons-specific routes |

### Service container

Symfony DI defined in `config/services/` (YAML files + one `addons.php` for
Addons-related services). `src/DependencyInjection/ContainerProvider.php`
exposes the Symfony container to legacy PS code via `$this->get(ServiceClass::class)`.
All services must be `public: true` for legacy compatibility.

### Views

- `views/templates/hook/*.tpl` - Smarty: CDC injection, recommended modules,
  empty category messages, connection toolbar
- `views/templates/hook/twig/` - Twig: MBO employee explanation, failed API user error
- `views/templates/admin/` - Twig: error page, AJAX layout (used by Symfony controllers)

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `MBO_CDC_URL` | `https://assets.prestashop3.com/dst/mbo/v1/mbo-cdc.umd.js` | CDC JS bundle URL |
| `DISTRIBUTION_API_URL` | `https://mbo-api.prestashop.com` | MBO distribution API |
| `ADDONS_API_URL` | `https://api-addons.prestashop.com` | Addons zip download API |
| `PS_MBO_SENTRY_CREDENTIALS` | (DSN) | Error monitoring |
| `PS_MBO_SENTRY_ENVIRONMENT` | `production` | Sentry env tag |

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
| `translations.yml` | push to 4.x | Crowdin sync |
| `install-mydemoshop.yml` | manual | test install on a live demo shop |

AI context files (`.ai-tools/`, `.claude/`, `CLAUDE.md`) are excluded from
all zip artifacts by CI and `make build-zip`.

## Skills

Project-specific skills live in `.claude/skills/`. Invoke them with the Skill tool or `/skill-name`:

| Skill | Trigger |
|-------|---------|
| `release-versioning` | Version bump + GitHub release for 4.x. No `--latest` flag — v5/master is the canonical latest. |

## Points of Attention

- **Remote management API**: calls from `mbo.prestashop.com` to the shop back-office
  are blocked by some hosters (Cloudflare, closed firewalls). This is a known
  limitation of the 4.x architecture; the mechanism was redesigned in v5.
- The CDC URL is injected at runtime from the `.env` file; if the CDC JS
  fails to load, `admin_mbo_module_cdc_error` renders a graceful fallback page
- `ConnectedClient` requires a valid PS Accounts token; anonymous shops silently
  fall back to `Client`
- PHPStan config is in `tests/phpstan/`; baseline and custom rules live there
- Sentry env vars use the `PS_MBO_` prefix (`PS_MBO_SENTRY_CREDENTIALS`,
  `PS_MBO_SENTRY_ENVIRONMENT`) to avoid conflicts with other modules on shared servers
