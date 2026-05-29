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

- Retrieve required services via `$this->getRequiredService(ServiceClass::class)` (trait `Traits\ResolvesServices`), never via `new`. It resolves from the container and throws `ExpectedServiceNotFoundException` when the service is missing, so callers no longer repeat the `get() + null-check + throw` boilerplate.
- Use a `::class` reference where the service is registered by class name. PS-core ids without a class alias (e.g. the `router` service) are passed as a string id; keep a `@var` annotation on those calls for type narrowing.
- Wrap service retrieval in try/catch; on exception call `ErrorHelper::reportError($e)` then return the safe neutral value (`''` for display hooks, not `false`).

## Hook return types

- Display hooks (`hookDisplay*`, `hookDashboard*`): return type `false|string`, return `''` on error (not `false`)
- Action hooks (`hookAction*`): typically `void` or `array`; document expected shape if array

## Exception handling in hooks

The rule differs by hook type.

### Display, list, and rendering hooks

A hook is called inside a PrestaShop workflow (page render, module list build...). An uncaught exception propagates up and breaks the entire workflow for the merchant, not just this module.

**Every display/list/rendering hook must be wrapped in a top-level try/catch(\Throwable).** No exception may bubble out.

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
        $service = $this->getRequiredService(MyService::class);
        return $service->enrich($params['list']);
    } catch (\Throwable $e) {
        ErrorHelper::reportError($e);
        return [];
    }
}
```

Return a safe neutral value on failure: `''` for display hooks, `[]` for hooks returning arrays, the unmodified input for hooks enriching a list.

### Lifecycle hooks: `actionBeforeInstallModule` and `actionBeforeUpgradeModule`

These hooks are the **exception to the rule above**. PS9 `ModuleController::moduleAction()` wraps the entire dispatch in a try/catch and surfaces `$e->getMessage()` as the JSON error message shown to the merchant. Throwing is the documented way to abort an install/upgrade with a translated, user-facing reason.

**Do NOT wrap the top-level body in try/catch(\Throwable) for these hooks.** Swallowing the exception would let PS Core continue past a failed Addons download and produce an opaque failure for the merchant.

Service retrieval failures may still be caught individually (to report the error), but exceptions from the business operation (Addons download, ActionsManager) must propagate.

```php
// CORRECT for actionBefore*Module
public function hookActionBeforeInstallModule(array $params): void
{
    try {
        $client = $this->getRequiredService(ApiClient::class);
    } catch (\Exception $e) {
        ErrorHelper::reportError($e);
        return;
    }

    // Let this throw: PS9 ModuleController catches it and surfaces getMessage() to the merchant
    $actionsManager->install($moduleId);
}
```

## CDC-injecting hooks

Hooks that inject CDC content must call `$this->smartyDisplayTpl()` from `HaveCdcComponent`, not Smarty directly. The `admin_mbo_module_cdc_error` route must be assigned before rendering.
