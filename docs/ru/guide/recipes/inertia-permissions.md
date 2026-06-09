# Inertia + права

Как передать права пользователя во Vue/React-компоненты через Inertia.js.

## Глобальный share через middleware

```php
// app/Http/Middleware/ShareAzGuardData.php
public function handle(Request $request, Closure $next): Response
{
    if ($user = $request->user()) {
        Inertia::share([
            'auth' => [
                'user'        => $user->only('id', 'name', 'email'),
                'permissions' => $user->getPermissionKeys(),
                'roles'       => $user->getRoleNames(),
            ],
        ]);
    }
    return $next($request);
}
```

## Vue composable

```js
// resources/js/composables/useAuth.js
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

export function useAuth() {
    const page = usePage()

    const can = (permission) =>
        page.props.auth.permissions.includes(permission)

    const hasRole = (role) =>
        page.props.auth.roles.includes(role)

    return { can, hasRole, user: computed(() => page.props.auth.user) }
}
```

```vue
<script setup>
import { useAuth } from '@/composables/useAuth'
const { can } = useAuth()
</script>

<template>
  <button v-if="can('app.posts.edit')" @click="edit">
    Редактировать
  </button>
</template>
```
