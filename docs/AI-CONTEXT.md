# AI Context — AzGuard

## В пакете

- Gate, миграции, `Role`, `HasAzGuard`, `Panel`, `PanelProvider`
- `PolicyAttributeRegistrar`, `GateAbility`, `GuardPolicy`, `CheckPermission`, `CheckAccess`
- `AuthorizesPermission`, `SkipGuardCheck`, `RoleOnly`
- `resolvePermission`, `AzGuard::permission()`, `guard:doctor`
- Generators: `make:guard-panel`, `make:guard-permission`, `make:guard-policy`, `make:guard-abilities`, `make:guard-role`
- `AbilitiesDto`, `azguard/filament` (`GuardResource`)

## Не в пакете

- Модели и enum домена приложения
- Inertia shared layout (глобальные permissions) — по решению приложения

## Контракт роли

| Поле | Значение |
|------|----------|
| `roles.name` | slug |
| `roles.class_name` | FQCN класса роли |

## Чеклист нового permission

1. case в enum guard-домена
2. метод политики + `#[GateAbility]` (или `#[RoleOnly]` для role-only)
3. `#[CheckPermission]` на контроллере
4. ключ в Abilities DTO (если UI)
5. resolved string в роли
6. `php artisan guard:doctor`
7. тест allow/deny

См. [recipes.md](guide/recipes.md), [filament.md](guide/filament.md).
