# Доменная структура

Зеркалировать `App\Models\{Domain}\` и `App\Http\Controllers\{Domain}\`:

```
app/Guards/App/Documents/
  Permissions/DocumentsPermission.php
  Policies/DocumentsPolicy.php
  Abilities/DocumentsAbilities.php
```

Модель для policy: `#[AzGuardPolicy(model: Document::class)]` или автоиз `{Domain}/Policies/{Name}Policy.php` → `App\Models\{Domain}\{SingularDomain}`.
