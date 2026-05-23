# Панели app и admin

| Панель | ID | Назначение |
|--------|-----|------------|
| App | `app` | Inertia, API |
| Admin | `admin` | Filament |

Роли **не пересекаются**: app-роли в `Guards/App/Roles`, admin — в `Guards/Admin/Roles`.

Resolved permissions: `app.*` vs `admin.*`.

## Filament

См. [filament.md](filament.md).
