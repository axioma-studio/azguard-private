# Архитектура панели AzGuard

Каждая панель — изолированный guard-модуль в `app/Guards/{PanelName}/`.

## Структура

- **{Panel}GuardPanelProvider.php** — регистрация панели, discover policies, Gate.
- **Roles/** — классы ролей (`RoleInterface`).
- **{Domain}/Permissions/** — backed enum.
- **{Domain}/Policies/** — политики с `#[GateAbility]`.
- **{Domain}/Abilities/** — DTO для Inertia (опционально).
- **Scopes/**, **Plugins/** — по необходимости.

## Поток проверки

1. HTTP: `CheckPermission` → `Gate::allows(resolved, args)`.
2. Gate вызывает callback политики (зарегистрирован через `GateAbility`).
3. Политика: `hasAzPermission(resolved)` + доменные правила.

См. также: [concept.md](concept.md), [permissions.md](permissions.md), [policies-and-gates.md](policies-and-gates.md).
