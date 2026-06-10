# azguard-testing

Use when adding or changing tests in this package.

## Stack

- **Pest 4** — all tests use Pest syntax, never PHPUnit assertions directly
- **Orchestra Testbench** (`^9.0|^10.0`) — full Laravel app context for integration tests
- Base class: `AzGuard\Tests\TestCase` (extends `Orchestra\Testbench\TestCase`)
- Context tests: `AzGuard\Tests\ContextTestCase`

## Test Layout

```
tests/
  Pest.php          — uses() binding, global helpers
  TestCase.php      — SQLite in-memory, loads migrations, sets cache=array
  ArchTest.php      — architecture constraints (no debug calls, strict types, etc.)
  Feature/          — integration tests against a real Laravel+DB context
  Unit/             — isolated unit tests, minimal DB usage
  Stubs/            — fake User, Post, Role, Policy, Panel classes
  Support/          — shared helpers (InteractsWithGuard)
```

## Key Helpers

- `createUserWithPermissions(array $permissions): User` — creates user, assigns direct permissions
- `createUserWithRole(string $roleName): User` — creates user, assigns role by name
- `actingAsGuardRole(string $roleClass): User` — creates user with a class-based role
- `assertGateAllows(string $panel, UnitEnum $permission, ...$args)` — panel-scoped Gate assertion

## Workflow

1. Write the smallest failing test for the requested behavior.
2. Run `composer test:unit -- --filter=YourTest` during iteration.
3. Run `composer test` before completing the task.
4. Cover happy path, unhappy path, and relevant edge cases.
5. Feature tests go in `tests/Feature/`, unit tests in `tests/Unit/`.

## Do Not

- Remove tests because they are inconvenient to fix.
- Test internal implementation details — test observable behavior.
- Use `PHPUnit\Framework\Assert` — prefer Pest's `expect()` API.
- Leave temporary scaffold tests in the suite.
