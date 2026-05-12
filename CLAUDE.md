# ps_mbo - AI Developer Context

## Role in the Ecosystem

PrestaShop module (Back-Office) that surfaces the Addons catalog, manages module
installation/upgrade lifecycles, and integrates with PrestaShop Accounts, the
MBO distribution API, and the Addons API.

Ecosystem map: `.ai-tools/ECOSYSTEM_CONTEXT.md`

Key upstream/downstream:
- `mbo.prestashop.com` feeds module data to this module (Distribution API)
- `ps_mbo` downloads zip artifacts from `addons.prestashop.com`
- Accounts integration via `prestashop/prestashop-accounts-installer`

## Stack

- PHP 8.1+, strict_types everywhere
- PrestaShop 8.x / 9.x (entry guard: `version_compare(_PS_VERSION_, '8.0.2', '<')`)
- Symfony 6.4 (DI container, Workflow, String)
- PHPUnit 9.x, PHP-CS-Fixer 3.x, PHPStan 1.x

## Architecture

### Main class composition

`ps_mbo.php` extends `Module` and composes behaviour via three traits:

```
ps_mbo
  UseHooks              - registers/dispatches all PS hooks
    Hooks\UseXxx...     - one file per hook in src/Traits/Hooks/
  HaveTabs              - admin tab registration
  HaveConfigurationPage - admin config page (stripped from production builds)
```

Each PS hook lives in its own trait file (`src/Traits/Hooks/UseXxxHook.php`).
`UseHooks::bootHooks()` calls `bootXxx()` on each trait at module init.

### Service layer

Symfony container defined in `config/services/` (YAML + one PHP file for Addons).
`src/DependencyInjection/ContainerProvider.php` exposes the container to
legacy PS code.

### API clients

- `src/Distribution/` - MBO API (BaseClient -> ConnectedClient/Client)
- `src/Addons/ApiClient.php` - Addons catalog API

### Module collection

`src/Module/` - Collection, Builder, Filters, Repository pattern for module lists
returned to the front-office hooks.

## Key Commands

```bash
composer install          # install dependencies
composer test             # run PHPUnit
composer lint             # php-cs-fixer (dry-run)
make build-zip            # local zip (dev)
```

## Conventions

- PSR-12, `declare(strict_types=1)` at top of every file
- Each hook = one trait in `src/Traits/Hooks/`; boot logic via `bootXxx()` pattern
- Service aliases must be `public: true` (legacy PS compatibility)
- No overrides; hooks only
- `HaveConfigurationPage.php` is deleted from the production zip by CI: keep
  production-facing code out of that file

## CI / Artifacts

- `build-release.yml` - builds zip, uploads to GCS (preprod) or GitHub release (prod)
- `php.yml` - linter, cs-fixer, phpstan, phpunit
- `translations.yml` - Crowdin sync
- AI context files (`.ai-tools/`, `.claude/`, `CLAUDE.md`) are excluded from
  all production zip artifacts

## Points of Attention

- The Sentry transport is blocking by default in PHP-FPM; see `src/Handler/` for
  the non-blocking override (recent fix: 528fe3a5)
- `ConnectedClient` requires a valid Accounts token; unauthenticated shops fall
  back to `Client`
- PHPStan config lives in `tests/phpstan/`; custom bootstrap in `tests/bootstrap.php`
