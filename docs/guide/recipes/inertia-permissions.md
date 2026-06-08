# Sharing Permissions with Inertia

When building an Inertia.js SPA with Vue or React, you typically need to know the current user's permissions on the frontend to show/hide UI elements.

## Approach

Share a permission map via `HandleInertiaRequests` middleware so it's available as a prop on every page.

## Setup

```php
// app/Http/Middleware/HandleInertiaRequests.php
use AzGuard\Facades\AzGuard;
use App\AzGuard\App\Permissions\DocumentsPermission;
use App\AzGuard\App\Permissions\InvoicesPermission;

public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'auth' => [
            'user' => $request->user(),
            // Share only the permissions relevant to the frontend
            'permissions' => $request->user() ? [
                'documents' => [
                    'view'   => $request->user()->hasPermission(DocumentsPermission::View),
                    'create' => $request->user()->hasPermission(DocumentsPermission::Create),
                    'edit'   => $request->user()->hasPermission(DocumentsPermission::Edit),
                    'delete' => $request->user()->hasPermission(DocumentsPermission::Delete),
                ],
                'invoices' => [
                    'view'   => $request->user()->hasPermission(InvoicesPermission::View),
                    'export' => $request->user()->hasPermission(InvoicesPermission::Export),
                ],
            ] : [],
        ],
    ]);
}
```

## Using the TypeScript export

Alternatively, export all permissions to TypeScript and use them with `usePage()`:

```bash
php artisan azguard:export-ts
# outputs resources/js/permissions.ts
```

```typescript
// resources/js/permissions.ts (auto-generated)
export const Permissions = {
  app: {
    documents: {
      view:   'app.documents.view',
      create: 'app.documents.create',
    },
  },
} as const;
```

```vue
<!-- resources/js/Pages/Documents/Index.vue -->
<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { Permissions } from '@/permissions'

const page = usePage()

// Type-safe permission check
const canCreate = computed(
  () => page.props.auth.permissions?.documents?.create
)
</script>

<template>
  <div>
    <CreateButton v-if="canCreate" />
  </div>
</template>
```

## React equivalent

```tsx
import { usePage } from '@inertiajs/react'

export function DocumentsPage() {
    const { auth } = usePage().props
    const canCreate = auth.permissions?.documents?.create

    return (
        <div>
            {canCreate && <CreateButton />}
        </div>
    )
}
```

## Important: frontend permissions are for UX only

Always validate permissions on the server side. Frontend permission checks are for hiding/showing UI elements, not for security — a user can modify frontend state. Every write action must be protected by `#[CheckPermission]` or `Gate::allows()` on the backend.
