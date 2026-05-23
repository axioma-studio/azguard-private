# Рецепты AzGuard

## 1. Новый CRUD-домен

1. `php artisan make:guard-panel App Invoices --role=Member`
2. Добавить cases: `php artisan make:guard-permission App Invoices Export`
3. Дополнить методы в `InvoicesPolicy` и `#[GateAbility]`
4. `#[CheckPermission]` на контроллере + middleware `azguard.panel` / `check.access`
5. Resolved permission в роли: `AppGuard::permission(InvoicesPermission::View)`
6. Pest: allow/deny через `Gate::allows`

## 2. Workflow permission

- case в enum: `Publish = 'invoices.workflow.publish'`
- метод политики с доменной логикой
- в роли — полная строка `app.invoices.workflow.publish`

## 3. Filament Resource (admin)

```php
final class UserResource extends GuardResource
{
    protected static function guardPanel(): string
    {
        return 'admin';
    }

    protected static function viewPermission(): UnitEnum
    {
        return AdminPermission::UsersManage;
    }
}
```

## 4. Inertia Abilities

- `DocumentsAbilities extends AbilitiesDto`
- `toArray()` на странице Show/Index
- resolved strings в `abilityMap()`, short keys только в PHP enum

## 5. Role-only permission

Для прав без Gate (только hasAzPermission в роли):

```php
#[RoleOnly]
case DashboardView = 'dashboard.view';
```

## 6. Диагностика

```bash
php artisan guard:doctor
php artisan guard:doctor --panel=app
```

## Чеклист

- [ ] enum case
- [ ] policy + GateAbility
- [ ] CheckPermission / SkipGuardCheck
- [ ] роль с resolved string
- [ ] тест
- [ ] guard:doctor без ошибок
