<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    | Eloquent models used by AzGuard. Replace with your own subclasses if needed.
    */
    'models' => [
        'role' => \AzGuard\Models\Role::class,
        'scope' => \AzGuard\Models\ModelHasScope::class,
        'direct_grant' => \AzGuard\Models\DirectGrant::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Models Namespace
    |--------------------------------------------------------------------------
    | Application namespace used when resolving model classes by name.
    */
    'models_namespace' => 'App\\Models\\',

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    | Override these if the default names conflict with existing tables.
    */
    'table_names' => [
        'roles' => 'roles',
        'model_has_roles' => 'model_has_roles',
        'model_has_scopes' => 'model_has_scopes',
        'direct_grants' => 'az_direct_grants',
    ],

    /*
    |--------------------------------------------------------------------------
    | Column Names
    |--------------------------------------------------------------------------
    | Customize column names (e.g. for UUID instead of auto-increment keys).
    | Set 'role_pivot_key' to 'uuid' to enable UUID support.
    */
    'column_names' => [
        'role_pivot_key' => null,      // null = auto (id)
        'model_morph_key' => 'model_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Panels
    |--------------------------------------------------------------------------
    | AzGuard panel providers. Each entry is the FQCN of a class extending PanelProvider.
    */
    'panels' => [],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => [
        'check_access_alias' => 'check.access',
        'register_middleware_in_appServiceProvider' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    | Per-user permission caching across requests.
    | store: 'default' | 'redis' | 'memcached' | 'file' | 'array'
    | Use 'array' to disable cross-request caching (in-memory only, good for tests).
    | expiration_time: TTL in seconds. null = no expiry.
    */
    'cache' => [
        'store' => 'array',
        'expiration_time' => 3600,
        'key' => 'azguard.permissions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Grant Sources
    |--------------------------------------------------------------------------
    | Control which GrantSources are active and their priority order.
    | null (default) = all built-in sources active, sorted by GrantPriority enum.
    | Provide an explicit list to restrict or reorder:
    |
    |   'grant_sources' => [
    |       \AzGuard\Registry\Sources\ClassRoleGrantSource::class,
    |       \AzGuard\Registry\Sources\DatabaseRoleGrantSource::class,
    |       // Omit DirectGrantSource to disable direct grants at source level
    |   ],
    */
    'grant_sources' => null,

    /*
    |--------------------------------------------------------------------------
    | Fail-fast on GrantSource Exception
    |--------------------------------------------------------------------------
    | When true, any Throwable from a GrantSource::permissionsFor() call
    | propagates immediately, failing the entire authorization pipeline.
    | When false (default), the failing source is skipped and a warning is logged.
    | Recommended: true in tests, false in production.
    */
    'fail_on_source_exception' => false,

    /*
    |--------------------------------------------------------------------------
    | Features (Feature Flags)
    |--------------------------------------------------------------------------
    | Enable only what you need. All flags default to false for maximum
    | backwards compatibility.
    */
    'features' => [
        'wildcard_permission' => false, // Wildcards like 'admin.*'
        'teams' => false, // Multi-team / tenant isolation
        'audit_log' => false, // Log role assignment and revocation events
        'direct_grants' => true,  // Direct grants (HasDirectGrants + az_direct_grants table)
    ],

    /*
    |--------------------------------------------------------------------------
    | Teams
    |--------------------------------------------------------------------------
    | Settings for multi-team mode (requires features.teams = true).
    */
    'teams' => [
        'foreign_key' => 'team_id',
    ],

];
