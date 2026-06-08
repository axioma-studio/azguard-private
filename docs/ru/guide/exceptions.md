# Исключения

## Список исключений

| Класс | Когда бросается |
|---|---|
| `UnauthorizedException` | Нет права доступа (middleware / `#[CheckPermission]`) |
| `MissingPanelConfigException` | Панель не найдена в `config/azguard.php` |
| `InvalidRoleException` | Класс роли не реализует `RoleInterface` |
| `ExpiredGrantException` | Обращение к истёкшему прямому гранту |
| `DuplicateRoleException` | Попытка назначить роль повторно |

## Глобальная обработка

```php
// bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (UnauthorizedException $e, Request $request) {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Недостаточно прав.',
                'required' => $e->requiredPermission(),
            ], 403);
        }
        return redirect()->route('dashboard')
            ->with('error', 'У вас нет доступа к этому разделу.');
    });
})
```

## Кастомные сообщения

```php
public function render($request)
{
    if ($this instanceof UnauthorizedException) {
        $messages = [
            'app.posts.delete' => 'Вы не можете удалять публикации.',
            'admin.users.ban'  => 'Только главный администратор может блокировать пользователей.',
        ];
        $message = $messages[$this->requiredPermission()] ?? 'Доступ запрещён.';
        return response()->json(['message' => $message], 403);
    }
}
```
