# Policies и Gate

## Регистрация

`PanelProvider` при boot:

1. Сканирует `**/Policies/**/*Policy.php`.
2. `PolicyAttributeRegistrar` регистрирует `#[GateAbility]` → `Gate::define(resolvedAbility, [Policy, method])`.
3. `Gate::policy($model, $policy)` — если задан `#[AzGuardPolicy(model: ...)]` или выведен путь домена.

## Политика

```php
#[GateAbility(permission: DocumentsPermission::View)]
public function canView(User $user, Document $document): bool
{
    return $user->hasAzPermission(AppGuard::permission(DocumentsPermission::View))
        && $user->id === $document->user_id;
}
```

## Gate::before

Только wildcard `*` в роли возвращает `true` без вызова политики.
