---
layout: home

hero:
  name: ""
  text: ""
  tagline: ""
  image:
    src: /logo.svg
    alt: AzGuard
  actions:
    - theme: brand
      text: Начать →
      link: /ru/guide/quick-start
    - theme: alt
      text: Почему AzGuard?
      link: /ru/guide/why-azguard
    - theme: alt
      text: GitHub
      link: https://github.com/axioma-studio/azguard-private
---

<div class="az-hero-text">
  <h1>Ваши права хранятся в Git,<br>а не в темноте.</h1>
  <p class="az-lead">AzGuard — это <strong>code-first RBAC-пакет для Laravel</strong>. Роли — PHP-классы. Права — типизированные enum-кейсы. База данных хранит только связки user→role, но никогда не хранит сам каталог прав.</p>
</div>

---

## Почему разработчики выбирают AzGuard

<div class="az-features">

**🏗️ Code-first — никаких magic-строк**
Права — это enum-кейсы PHP. Переименуйте один — IDE и PHPStan поймают каждую сломанную ссылку до CI. Никаких опечаток `'edit-posts'`, которые падают только в рантайме.

**🎛️ Изоляция по панелям**
`app.*`, `admin.*`, `api.*` — полностью независимые пространства имён. Один User-model может иметь роли в нескольких контекстах без взаимного влияния.

**⚡ Нативный Laravel Gate**
AzGuard подключается через `Gate::before()`. Все стандартные примитивы — `@can`, `Gate::allows()`, `$this->authorize()`, политики — работают без изменений.

**🩺 Встроенная диагностика**
`artisan azguard:doctor` сканирует конфиг, миграции и определения ролей и сообщает о несоответствиях до продакшена.

**🎯 Прямые гранты с TTL**
Выдайте одному пользователю одно право на 1 час без изменения ролей. Идеально для бета-функций и временного доступа.

**🔌 Контекст (опционально)**
Привяжите runtime-контекст (tenant, команда, проект) к каждой проверке прав. Нулевые накладные расходы, когда не используется.

</div>

---

## Три строки, которые говорят всё

::: code-group

```php [1. Определите]
enum DocumentsPermission: string implements PermissionInterface
{
    #[GateAbility]
    case View   = 'documents.view';
    case Create = 'documents.create';
    case Edit   = 'documents.edit';
    case Delete = 'documents.delete';
}
```

```php [2. Защитите]
#[CheckPermission(DocumentsPermission::View)]
public function index(): Response
{
    return Inertia::render('Documents/Index');
}
```

```php [3. Проверьте]
$user->hasPermission(DocumentsPermission::View);
Gate::allows('app.documents.view');
@can('app.documents.edit') ... @endcan
```

:::

---

## Быстрая установка

```bash
composer require axioma-studio/azguard
php artisan vendor:publish --tag=azguard-config
php artisan vendor:publish --tag=azguard-migrations
php artisan migrate
```

Добавьте трейт в модель User:

```php
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable
{
    use HasAzGuard;
}
```

→ [Полное руководство по установке](/ru/guide/installation) · [Быстрый старт за 5 минут](/ru/guide/quick-start)
