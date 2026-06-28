# Тестирование

## Настройка

В тестах AzGuard удобно отключить кросс-реквест кеш, выставив `array`-стор:

```php
// config/az-guard.php (или в TestCase через config())
config(['az-guard.cache.store' => 'array']);
```

`array`-стор — это и есть значение по умолчанию: кеш живёт только в рамках одного
запроса и автоматически сбрасывается между тестами.

## Пользователи с ролями

```php
public function test_editor_can_edit_posts(): void
{
    $user = User::factory()->create();
    $user->assignRole('editor');

    $post = Post::factory()->create();

    $this->actingAs($user)
         ->put("/posts/{$post->id}", ['title' => 'Новый заголовок'])
         ->assertOk();
}

public function test_viewer_cannot_edit_posts(): void
{
    $user = User::factory()->create();
    $user->assignRole('viewer');

    $post = Post::factory()->create();

    $this->actingAs($user)
         ->put("/posts/{$post->id}", ['title' => 'Новый заголовок'])
         ->assertForbidden();
}
```

## Пользователь с прямым грантом

```php
public function test_user_with_direct_grant(): void
{
    $user = User::factory()->create();

    // Выдаём право напрямую на панель 'app'
    $user->grant(ReportsPermission::Export, 'app');

    $this->assertTrue($user->hasPermission(ReportsPermission::Export));
}
```

## Проверка прав без HTTP

```php
public function test_without_http_request(): void
{
    $user = User::factory()->create();
    $user->assignRole('editor');

    // Прямая проверка
    $this->assertTrue($user->hasPermission('app.posts.edit'));

    // Через Gate
    $this->actingAs($user);
    $this->assertTrue(Gate::allows('app.posts.edit'));
}
```

## Тестирование Blade-директив

```php
public function test_blade_shows_edit_button_for_editor(): void
{
    $user = User::factory()->create();
    $user->assignRole('editor');

    $html = $this->actingAs($user)
                 ->get('/posts/1')
                 ->getContent();

    $this->assertStringContainsString('Редактировать', $html);
}
```
