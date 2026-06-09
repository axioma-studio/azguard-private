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
    | Models
    |--------------------------------------------------------------------------
    | Eloquent models used by AzGuard. Replace with your own subclasses.
    */
    'models' => [
        'role'         => \AzGuard\Models\Role::class,
        'scope'        => \AzGuard\Models\ModelHasScope::class,
        'direct_grant' => \AzGuard\Models\DirectGrant::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Panels
    |--------------------------------------------------------------------------
    | List every class that extends AzGuard\PanelProvider.
    | Each panel defines its own permission namespace and catalog builders.
    */
    'panels' => [
        // \App\AzGuard\Panels\AppPanelProvider::class,
        // \App\AzGuard\Panels\AdminPanelProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    | Override if existing tables conflict. All four keys must be present.
    */
    'table_names' => [
        'roles'            => 'roles',
        'model_has_roles'  => 'model_has_roles',
        'model_has_scopes' => 'model_has_scopes',
        'direct_grants'    => 'az_direct_grants',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    | 'store'  — any store from config/cache.php. Use 'array' to disable
    |            cross-request caching (in-memory only, good for tests).
    | 'expiration_time' — TTL in seconds. null = never expires.
    */
    'cache' => [
        'store'           => 'array',
        'expiration_time' => 3600,
        'key'             => 'azguard.permissions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    | Toggle optional capabilities. All disabled by default for maximum
    | backwards compatibility.
    */
    'features' => [
        'wildcard_permission' => false,
        'direct_grants'       => true,
    ],

];
```

::: tip Tests
Set `cache.store` to `'array'` (the default) to keep permissions in-memory only, which prevents stale state between test cases.
:::
