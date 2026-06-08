# Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=az-guard-config
```

This creates `config/az-guard.php`. Below is the full file with annotations.

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Panels
    |--------------------------------------------------------------------------
    |
    | List every PanelProviderInterface implementation.
    | Each panel defines its own permissions, roles, and namespace prefix.
    |
    */
    'panels' => [
        // \App\Guards\App\AppPanelProvider::class,
        // \App\Guards\Admin\AdminPanelProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | AzGuard caches resolved permissions per user per panel.
    | Set 'store' to any cache store defined in config/cache.php.
    | Set 'ttl' to 0 to disable caching (useful in tests).
    |
    */
    'cache' => [
        'store' => env('AZGUARD_CACHE_STORE', 'default'),
        'ttl'   => env('AZGUARD_CACHE_TTL', 3600), // seconds
        'prefix' => 'azguard',
    ],

    /*
    |--------------------------------------------------------------------------
    | Wildcard permission
    |--------------------------------------------------------------------------
    |
    | When a role's permissions() returns ['*'], Gate::before returns true
    | for all abilities. Set to false to disable wildcard support entirely.
    |
    */
    'wildcard' => true,

    /*
    |--------------------------------------------------------------------------
    | User model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model that uses HasAzGuard.
    |
    */
    'user_model' => env('AZGUARD_USER_MODEL', \App\Models\User::class),

    /*
    |--------------------------------------------------------------------------
    | Table names
    |--------------------------------------------------------------------------
    |
    | Override if you need custom table names (e.g. multi-tenant setups).
    |
    */
    'tables' => [
        'roles'         => 'az_guard_roles',
        'role_user'     => 'az_guard_role_user',
        'grants'        => 'az_guard_direct_grants',
        'permissions'   => 'az_guard_permissions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Gate registration
    |--------------------------------------------------------------------------
    |
    | Controls when AzGuard registers Gate::before and Gate::define calls.
    | Disable if you manage Gate registration manually.
    |
    */
    'auto_register_gate' => true,

];
```

## Environment variables

| Variable | Default | Description |
|---|---|---|
| `AZGUARD_CACHE_STORE` | `default` | Cache store for permission resolution |
| `AZGUARD_CACHE_TTL` | `3600` | Permission cache TTL in seconds |
| `AZGUARD_USER_MODEL` | `App\Models\User` | Eloquent user model |

::: tip Tests
Set `AZGUARD_CACHE_TTL=0` in your `phpunit.xml` to disable caching in tests and avoid stale permission state between test cases.
:::
