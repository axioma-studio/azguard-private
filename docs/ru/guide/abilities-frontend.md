# Права на фронтенде

AzGuard позволяет передавать права пользователя в JavaScript через API или через Inertia.

## Inertia.js

```php
// HandleInertiaRequests.php
public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'auth' => [
            'user' => $request->user(),
            'permissions' => $request->user()
                ?->getAllPermissionKeys()  // ['app.posts.view', 'app.posts.edit', ...]
                ?? [],
        ],
    ]);
}
```

```js
// resources/js/app.js — глобальный хелпер
const can = (permission) => usePage().props.auth.permissions.includes(permission)

// В компоненте Vue
<a v-if="can('app.posts.edit')" :href="editUrl">Редактировать</a>
```

## API-эндпойнт

```php
// routes/api.php
Route::get('/me/permissions', function (Request $request) {
    return response()->json([
        'permissions' => $request->user()->getAllPermissionKeys(),
        'roles'       => $request->user()->getRoleNames(),
    ]);
})->middleware('auth:api');
```

## React / Vue (SPA)

```js
// Загрузите права при инициализации
const { data } = await axios.get('/api/me/permissions')
const permissions = new Set(data.permissions)

// Хелпер
$can = (p) => permissions.has(p)

// В JSX
{$can('app.posts.delete') && <button onClick={onDelete}>Удалить</button>}
```

::: warning
Проверка прав на фронтенде — это только **UX-оптимизация** (скрыть кнопки / пункты меню). Реальная авторизация всегда должна проходить на сервере.
:::

→ [Рецепт: Inertia + права](/ru/guide/recipes/inertia-permissions)
