# azguard-scaffold

Use when adding a new class, interface, trait, or component to one of the three packages.

## Package Namespaces

| Package | Namespace | Path |
|---|---|---|
| core | `AzGuard\` | `packages/core/src/` |
| filament | `AzGuard\Filament\` | `packages/filament/src/` |
| context | `AzGuard\Context\` | `packages/context/src/` |

## Workflow

1. Identify which package the new component belongs to.
2. Check existing classes in the target namespace — reuse before creating.
3. Place the file in the correct subdirectory matching the namespace segment.
4. Add `declare(strict_types=1)` at the top of every new file.
5. If adding a service, register it in the relevant `ServiceProvider`.
6. Write a corresponding test (use `azguard-testing` skill).
7. Run `composer lint` and `composer analyse` before completing.

## Naming Conventions

- Contracts (interfaces): `Contracts/` subdirectory, no `Interface` suffix (e.g., `GrantSource`, not `GrantSourceInterface`)
- Exceptions: `Exceptions/` subdirectory, suffix `Exception`
- Commands: `Commands/` subdirectory, suffix `Command`
- Middleware: `Http/Middleware/` in core
- Events: `Events/` subdirectory, no suffix

## Do Not

- Add a new package-level dependency without discussion.
- Mix namespace segments (e.g., put a core class in filament namespace).
- Skip service provider registration for new services.
- Add hypothetical future abstractions — solve the current problem only.
