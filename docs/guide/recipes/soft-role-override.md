# Recipe: Soft Role Override

Sometimes a user needs elevated access for a limited time without being permanently assigned a higher role. A soft override keeps the audit trail clean — the base role is unchanged; the override is a direct grant with an expiry.

## Pattern

```php
use AzGuard\Models\AzDirectGrant;
use App\Guards\App\AppGuard;
use App\Guards\App\Permissions\DocumentsPermission;

// Grant publish access until end of sprint
AzDirectGrant::create([
    'user_id'    => $user->id,
    'panel'      => 'app',
    'permission' => AppGuard::permission(DocumentsPermission::Publish),
    'granted_by' => auth()->id(),
    'expires_at' => now()->addDays(14),
    'reason'     => 'Sprint 9 release manager',
]);
```

AzGuard merges direct grants with role permissions at resolution time. When the grant expires, access reverts to the user's base role automatically — no cleanup job required.

## Checking the override

```php
$user->hasAzPermission(DocumentsPermission::Publish); // true while grant is active
                                                       // false after expires_at
```

## Auditing overrides

```bash
php artisan azguard:list-permissions --user=42
```

Shows all active permissions including their source (role or direct grant) and expiry.

::: tip
For bulk temporary elevation, consider creating a DB-backed custom role with a named purpose and a scheduled job to remove users from it after the event.
:::
