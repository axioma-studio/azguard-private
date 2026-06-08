# Права на фронтенде

Передача прав пользователя на клиент для условного рендеринга в Vue/React/Inertia.

## Через Inertia SharedData

```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'abilities' => fn () => $request->user()
            ? $request->user()->abilities()
            : [],
    ];
}
```

## Vue 3 composable

```typescript
// composables/usePermissions.ts
import { usePage } from '@inertiajs/vue3'

export function usePermissions() {
    const abilities = computed(() => usePage().props.abilities as string[])

    const can = (permission: string) => abilities.value.includes(permission)
    const canAny = (permissions: string[]) =>
        permissions.some(p => abilities.value.includes(p))

    return { can, canAny }
}
```

```vue
<script setup>
import { usePermissions } from '@/composables/usePermissions'
const { can } = usePermissions()
</script>

<template>
    <button v-if="can('app.posts.edit')">Редактировать</button>
</template>
```

## React хук

```typescript
import { usePage } from '@inertiajs/react'

export function usePermissions() {
    const { abilities } = usePage<{ abilities: string[] }>().props
    return {
        can: (permission: string) => abilities.includes(permission),
    }
}
```
