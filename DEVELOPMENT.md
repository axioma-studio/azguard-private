# AzGuard — Development

## Local development

Test the package against a real Laravel app via a path repository.

```json
// the consuming app's composer.json
"repositories": [
    {
        "type": "path",
        "url": "../azguard/packages/core",
        "options": { "symlink": true }
    }
],
"require": {
    "axioma-studio/azguard-core": "@dev"
}
```

Mount or symlink the package directory into the app, then `composer update`.

## Quality commands

| Command | Tool | Description |
|---|---|---|
| `composer test` | Pest | Run the test suite |
| `composer test:types` | Pest | Type-coverage gate (min 98%) |
| `composer analyse` | PHPStan / Larastan | Static analysis (level 6) |
| `composer lint` / `lint:check` | Pint | Fix / check code style |
| `composer refactor` / `refactor:check` | Rector | Apply / preview refactorings |
| `composer mutate` | Infection | Mutation testing |
| `composer check` | — | Run every CI gate (style + analysis + refactor + types + tests) |
| `composer fix` | — | Auto-fix style and apply refactorings |

Feature tests use an in-memory SQLite database, so the `pdo_sqlite` /
`sqlite3` PHP extensions must be enabled.

## Conventions

- `declare(strict_types=1)` in every PHP file; PHPStan level 6; Pest 4.
- Permissions and roles are referenced by **enums and classes**, never magic
  strings (see the docs).
- Role contract: `roles.name` holds a slug (`admin`), `roles.class_name` holds
  the FQCN of the PHP role class (`App\Guards\App\Roles\AdminRole`).

## Git workflow

Branch from `main` (`feat/…`, `fix/…`), keep the suite green (`composer check`),
and open a Pull Request to `main`. Commits follow Conventional Commits.
