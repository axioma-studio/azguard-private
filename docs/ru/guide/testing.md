# Тестирование

## Настройка

```php
use AzGuard\Testing\AzGuardFake;

class PostControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Используем in-memory SQLite — быстро и изолированно
    }
}
```

## Назначение ролей в тестах

```php
/** @test */
public function editor_can_update_post(): void
{
    $user = User::factory()->create();
    $user->assignRole(EditorRole::class);
    $post = Post::factory()->create();

    $this->actingAs($user)
        ->put(route('posts.update', $post), ['title' => 'Новый заголовок'])
        ->assertOk();
}

/** @test */
public function viewer_cannot_update_post(): void
{
    $user = User::factory()->create();
    $user->assignRole(ViewerRole::class);
    $post = Post::factory()->create();

    $this->actingAs($user)
        ->put(route('posts.update', $post), ['title' => 'Новый заголовок'])
        ->assertForbidden();
}
```

## Pest-хелперы

```php
use AzGuard\Testing\InteractsWithAzGuard;

uses(InteractsWithAzGuard::class);

it('позволяет редактору редактировать посты', function () {
    $user = asEditor(); // создаёт User + assignRole(EditorRole::class)
    $post = Post::factory()->create();

    actingAs($user)
        ->put(route('posts.update', $post), [])
        ->assertOk();
});

it('запрещает просматривающему удалять посты', function () {
    $user = asViewer();
    $post = Post::factory()->create();

    actingAs($user)
        ->delete(route('posts.destroy', $post))
        ->assertForbidden();
});
```

## Fake-разрешения

```php
// Дать конкретное право без создания роли
AzGuardFake::grantPermission($user, PostsPermission::Delete);

// Запретить право принудительно
AzGuardFake::denyPermission($user, PostsPermission::Edit);

// Сбросить все
AzGuardFake::reset();
```
