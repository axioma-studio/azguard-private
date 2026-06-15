# PhpStorm Integration

AzGuard is built on PHP 8 enums and attributes, which PhpStorm understands natively. A few extra steps improve the experience further.

## IDE auto-complete for permission strings

Because permissions are enum cases, PhpStorm auto-completes `DocumentsPermission::` with all available cases — no plugin required.

```php
$user->hasPermission(DocumentsPermission::); // ← PhpStorm lists View, Create, Edit, Delete
```

## Laravel Idea / Laravel Plugin

If you use [Laravel Idea](https://plugins.jetbrains.com/plugin/13441-laravel-idea) or the free [Laravel](https://plugins.jetbrains.com/plugin/7532-laravel) plugin, `@can`, `Gate::allows()`, and `can:` middleware string arguments are resolved against registered Gate abilities.

AzGuard registers every permission key from its catalog as a Gate ability on boot, so these strings are recognized:

```php
// Laravel Idea resolves 'app.documents.view' to DocumentsPermission::View
$this->authorize('app.documents.view');
```

## .phpstorm.meta.php (manual fallback)

If you are not using a plugin, add a meta file to help PhpStorm infer `hasPermission()` arguments:

```php
// .phpstorm.meta.php
<?php
namespace PHPSTORM_META {
    override(\AzGuard\Concerns\HasPermissions::hasPermission(0), type(0));
}
```

## Run inspections

PhpStorm's **Enum cases** inspection catches typos in enum case usage at edit time. Enable it under:

> Settings → Editor → Inspections → PHP → General → Enum cases

## Xdebug step-through

The `Gate::before()` callback registered by AzGuard is a standard PHP closure. You can place a breakpoint inside `AzGuard\AzGuardServiceProvider::boot()` (where `Gate::before(...)` is registered) to step through the full permission resolution flow.
