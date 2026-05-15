---
paths:
  - "src/Traits/Hooks/*.php"
  - "src/Traits/*.php"
---

# Hook trait conventions

## File structure

Every hook trait must follow this order:
1. License header (AFL 3.0 block comment)
2. `declare(strict_types=1);`
3. `namespace PrestaShop\Module\Mbo\Traits\Hooks;`
4. `use` statements
5. `if (!defined('_PS_VERSION_')) { exit; }`
6. `trait Use{HookName}`

## Service access

- Always retrieve services via `$this->get(ServiceClass::class)`, never via `new`
- After `$this->get()`, check for `null` and throw `ExpectedServiceNotFoundException` if required
- Wrap service retrieval in try/catch; on exception call `ErrorHelper::reportError($e)` then return `''` (not `false`)

## Hook return types

- Display hooks (`hookDisplay*`, `hookDashboard*`): return type `false|string`, return `''` on error (not `false`)
- Action hooks (`hookAction*`): typically `void` or `array`; document expected shape if array

## NEVER let an exception escape a hook

A hook is called inside a PrestaShop workflow (module install, page render, module list build...). An uncaught exception propagates up and breaks the entire workflow for the merchant, not just this module.

**Every hook method must be wrapped in a top-level try/catch(\Throwable).** No exception — checked or unchecked — may bubble out.

```php
// WRONG — exception escapes, breaks the PS workflow
public function hookActionListModules(array $params): array
{
    $service = $this->get(MyService::class); // throws if container not ready
    return $service->enrich($params['list']);
}

// CORRECT
public function hookActionListModules(array $params): array
{
    try {
        $service = $this->get(MyService::class);
        if (null === $service) {
            throw new ExpectedServiceNotFoundException('...');
        }
        return $service->enrich($params['list']);
    } catch (\Throwable $e) {
        ErrorHelper::reportError($e);
        return [];
    }
}
```

Return a safe neutral value on failure: `''` for display hooks, `[]` for hooks returning arrays, the unmodified input for hooks enriching a list.

## Boot method

Each trait must have a `boot{TraitName}()` method (e.g. `bootUseDashboardZoneOne()`) called by `UseHooks::bootHooks()`. Put any per-hook service initialization here, not in the hook method itself.

## CDC-injecting hooks

Hooks that inject CDC content must call `$this->smartyDisplayTpl()` from `HaveCdcComponent`, not Smarty directly. The `admin_mbo_module_cdc_error` route must be assigned before rendering.

## Active hooks on 4.x (not in v5/master)

These hooks exist on 4.x and are NOT present in v5:
- `UseActionAdminControllerSetMedia`: injects CSS/JS for the connection toolbar and MBO employee explanation UI
- `UseActionGetAdminToolbarButtons`: adds MBO-specific buttons to the PS admin toolbar
- `UseDisplayAdminAfterHeader`: renders the connection toolbar (admin user status, register CTA)
- `hookDashboardZoneTwo`: secondary CDC zone on the module catalog page
