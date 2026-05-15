---
paths:
  - "tests/**/*.php"
---

# Testing conventions

## Framework and base classes

- PHPUnit 8.x/9.x
- Use `MockeryTestCase` (from `mockery/mockery`) when you need expressive mock expectations; use `createMock()` for simple stubs
- Workflow tests extend `AbstractTransitionTest` — check it for helper methods before writing boilerplate
- Namespace mirrors `src/`: `PrestaShop\Module\Mbo\Tests\*`

## Test structure

- `@dataProvider` for all parametrized cases; provider method name must be referenced in the docblock
- Provider methods return `yield` expressions, not arrays, for readability
- `setUp()` initializes shared state; each test method is self-contained beyond that
- `declare(strict_types=1)` is NOT required in test files (unlike `src/`)

## What to test

- Unit tests for pure logic (Filters, Workflow transitions, VO construction)
- Mock external dependencies (Distribution API clients, PS `Db`, `Module`) — not internal collaborators
- Do not test the PS framework itself; test the module's own behavior

## Assertions

- `assertEquals` for value equality, `assertSame` when type matters
- `expectException(FooException::class)` before the call that throws, never in `catch`
- Avoid `assertTrue(true)` or empty test bodies
