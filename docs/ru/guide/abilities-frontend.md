# Права на фронтенде

AzGuard может передавать права текущего пользователя на фронтенд — для условного рендеринга в Vue, React или Inertia.

## С Inertia.js

```php
// app/Http/Middleware/SharePermissions.php
public function handle(Request $request, Closure $next): Response
{
    if ($user = $request->user()) {
        Inertia::share([
            'permissions' => $user->getPermissionKeys(), // ['app.posts.view', ...]
            'roles'       => $user->getRoleNames(),      // ['editor']
        ]);
    }
    return $next($request);
}
```

```js
// resources/js/composables/usePermissions.js
import { usePage } from '@inertiajs/vue3'

export function usePermissions() {
    const { permissions } = usePage().props

    return {
        can: (permission) => permissions.includes(permission),
        hasRole: (role) => usePage().props.roles.includes(role),
    }
}

// В компоненте
const { can } = usePermissions()
// <button v-if="can('app.posts.edit')">Редактировать</button>
```

## С Livewire

```php
// В Livewire-компоненте
public function render(): View
{
    return view('livewire.post-actions', [
        'canEdit'   => auth()->user()->hasPermission(PostsPermission::Edit),
        'canDelete' => auth()->user()->hasPermission(PostsPermission::Delete),
    ]);
}
```

## Безопасность

::: warning
Передача прав на фронтенд — только для UI. Всегда проверяйте права на сервере в контроллерах или Gate.
:::
