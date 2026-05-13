---
paths:
  - "config/services/**/*.yml"
  - "config/services/**/*.yaml"
  - "config/services/**/*.php"
---

# Symfony DI configuration conventions

## Public services

All services must be reachable via `$this->get()` from legacy PS code. The `_defaults` block already sets `public: true` in each YAML file — do not remove it, and do not set `public: false` on individual services.

## Environment variables

API base URLs and credentials must come from env vars:

```yaml
arguments:
  $apiUrl: "%env(DISTRIBUTION_API_URL)%"
```

Never hardcode any URL directly in YAML.

## Autowire

- Use `autowire: true` only when constructor arguments can be resolved unambiguously from type hints
- If a service needs explicit `$apiUrl`, `$httpClient`, or other non-typed scalar args, list them under `arguments:` explicitly
- Do not mix `autowire: true` with a full `arguments:` block — pick one approach

## Factory pattern

For services built via a static factory method (e.g. Doctrine cache wrappers):

```yaml
factory: [ ClassName, staticMethodName ]
arguments:
  $pool: '@DependencyServiceId'
```

## File ownership

| File | Owns |
|------|------|
| `distribution.yml` | Distribution API clients and cache |
| `addons.yml` / `addons.php` | Addons API client and source handler |
| `accounts.yml` | ps_accounts integration services |
| `cdc.yml` | CDC context builder and related services |
| `modules.yml` | Module collection, repository, builder |
| `security.yml` | HMAC verifier and legacy API security (v8 remnant, read-only) |

Add new services to the file that owns their domain; do not dump everything into a catch-all file.
