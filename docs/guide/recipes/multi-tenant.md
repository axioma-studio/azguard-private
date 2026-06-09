# Multi-Tenant Roles

This recipe shows how to use AzGuard in a multi-tenant application where each tenant has its own set of custom roles.

## Approach

Use **static roles** for the base role set (every tenant gets them), and **dynamic roles** for custom tenant-specific roles created through the admin UI.

## Setup

```php
// app/AzGuard/App/Roles/ViewerRole.php — base role, same for all tenants
class ViewerRole implements RoleInterface
{
    public function getName(): string  { return 'viewer'; }
    public function getPanel(): string { return 'app'; }
    public function getLevel(): int    { return 1; }

    public function permissions(): array
    {
        return [
            DocumentsPermission::View,
            InvoicesPermission::View,
        ];
    }
}
```

## Isolating by tenant

Use the **context** package to scope permissions to a tenant:

```php
use AzGuard\Context\AuthorizationContext;
use AzGuard\Context\AuthorizationContextManager;

// Middleware — set context on every request
public function handle(Request $request, Closure $next): Response
{
    $tenant = $request->user()?->tenant;

    if ($tenant) {
        app(AuthorizationContextManager::class)->set(
            AuthorizationContext::for(
                panelId:     'app',
                contextType: 'tenant',
                contextId:   (string) $tenant->id,
            )
        );
    }

    return $next($request);
}
```

## Creating tenant-specific roles

```php
use AzGuard\Models\DynamicRole;

// In a controller or job
public function createRole(CreateRoleRequest $request, Tenant $tenant): JsonResponse
{
    $role = DynamicRole::create([
        'name'       => $request->name,
        'panel'      => 'app',
        'level'      => $request->level ?? 5,
        'tenant_id'  => $tenant->id,  // your application scope column
    ]);

    $role->syncPermissions($request->permissions);

    return response()->json(['role' => $role], 201);
}
```

## Checking in context

```php
// Permission check automatically includes the context
$user->hasPermissionIn('app', DocumentsPermission::View, [
    'contextType' => 'tenant',
    'contextId'   => (string) $tenant->id,
]);
```

See [Context](/guide/context) for the full context API.
