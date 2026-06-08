# Inertia + права

## Передача прав через SharedData

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

## Vue 3 + TypeScript

```typescript
// types/inertia.d.ts
export interface SharedProps {
    abilities: string[]
}

// composables/usePermissions.ts
import { usePage } from '@inertiajs/vue3'
import type { SharedProps } from '@/types/inertia'

export function useCan() {
    const { abilities } = usePage<SharedProps>().props
    return (permission: string) => abilities.includes(permission)
}
```

```vue
<script setup lang="ts">
import { useCan } from '@/composables/usePermissions'
const can = useCan()
</script>

<template>
  <div class="post-actions">
    <a v-if="can('app.posts.edit')" :href="editUrl">Редактировать</a>
    <button v-if="can('app.posts.delete')" @click="deletePost">Удалить</button>
  </div>
</template>
```

## React + TypeScript

```tsx
import { usePage } from '@inertiajs/react'

function usePermissions() {
    const { abilities } = usePage<{ abilities: string[] }>().props
    return { can: (p: string) => abilities.includes(p) }
}

export function PostActions({ post }: { post: Post }) {
    const { can } = usePermissions()
    return (
        <div>
            {can('app.posts.edit') && <EditButton post={post} />}
            {can('app.posts.delete') && <DeleteButton post={post} />}
        </div>
    )
}
```
