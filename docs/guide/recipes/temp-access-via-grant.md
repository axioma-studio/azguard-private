# Recipe: Temporary Access via Direct Grant

Direct grants let you give a user a single permission for a fixed period without modifying their role or creating a custom role.

## Use cases

- A contractor needs read access to a specific domain for two weeks
- A support engineer needs delete access while investigating a bug
- A reviewer needs publish access for a one-off release

## Creating the grant

```php
use AzGuard\Models\AzDirectGrant;
use App\Guards\App\AppGuard;
use App\Guards\App\Permissions\DocumentsPermission;

AzDirectGrant::create([
    'user_id'    => $contractor->id,
    'panel'      => 'app',
    'permission' => AppGuard::permission(DocumentsPermission::View),
    'granted_by' => auth()->id(),
    'expires_at' => now()->addWeeks(2),
    'reason'     => 'Audit review — ticket #4821',
]);
```

## Revoking early

```php
// Soft-delete (revoke) a specific grant
$grant = AzDirectGrant::where('user_id', $contractor->id)
    ->where('permission', AppGuard::permission(DocumentsPermission::View))
    ->first();

$grant->revoke(); // sets revoked_at, clears user cache
```

## Via Filament

Open **Direct Grants → Create**, select the user and permission from the dropdowns, set an expiry date, and save. The grant is active immediately.

To revoke: find the grant in the list and click **Revoke**.

## Via artisan (dev / seeding)

```bash
php artisan azguard:grant --user=42 --permission=app.documents.view --expires=2026-06-30 --reason="Audit"
```

## Cache invalidation

AzGuard automatically clears the user's permission cache when a grant is created or revoked. No manual cache flush needed.

::: tip
Grants are logged with `granted_by` and `reason`. Use these fields to maintain a meaningful audit trail for compliance.
:::
