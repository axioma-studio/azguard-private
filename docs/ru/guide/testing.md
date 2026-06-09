# Тестирование

## Настройка

AzGuard предоставляет вспомогательные трейты для тестов:

```php
use AzGuard\Testing\WithAzGuard;

class PostControllerTest extends TestCase
{
    use WithAzGuard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAzGuard(); // применяет миграции и чистит кеш
    }
}
```

## Пользователи с ролями

```php
public function test_editor_can_edit_posts(): void
{
    $user = User::factory()->create();
    $user->assignRole(EditorRole::class);

    $post = Post::factory()->create();

    $this->actingAs($user)
         ->put("/posts/{$post->id}", ['title' => 'Новый заголовок'])
         ->assertOk();
}

public function test_viewer_cannot_edit_posts(): void
{
    $user = User::factory()->create();
    $user->assignRole(ViewerRole::class);

    $post = Post::factory()->create();

    $this->actingAs($user)
         ->put("/posts/{$post->id}", ['title' => 'Новый заголовок'])
         ->assertForbidden();
}
```

## Хелперы withRole и withPermission

```php
// Быстрое создание пользователя с ролью
$editor = $this->userWithRole(EditorRole::class);

// Пользователь с прямым грантом
$user = $this->userWithPermission(ReportsPermission::Export);

// Пользователь без прав (по умолчанию)
$guest = User::factory()->create();
```

## Мокирование AzGuard

```php
public function test_without_real_db_permissions(): void
{
    $user = User::factory()->create();

    // Мокируем через Gate
    Gate::shouldReceive('allows')
        ->with('app.posts.edit')
        ->andReturn(true);

    $this->actingAs($user)
         ->put('/posts/1', ['title' => 'Test'])
         ->assertOk();
}
```

## Тестирование Blade-директив

```php
public function test_blade_shows_edit_button_for_editor(): void
{
    $user = User::factory()->create();
    $user->assignRole(EditorRole::class);

    $html = $this->actingAs($user)
                 ->get('/posts/1')
                 ->getContent();

    $this->assertStringContainsString('Редактировать', $html);
}
```
