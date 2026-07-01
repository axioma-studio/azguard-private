<?php

declare(strict_types=1);
use AzGuard\Models\DirectGrant;
use AzGuard\Models\ModelHasScope;
use AzGuard\Models\Role;

return [

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    | Eloquent models used by AzGuard. Replace with your own subclasses if needed.
    */
    'models' => [
        'role' => Role::class,
        'scope' => ModelHasScope::class,
        'direct_grant' => DirectGrant::class,
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
        'role_permissions' => 'az_guard_role_permissions',
        'direct_grants' => 'az_direct_grants',
    ],

    /*
    |--------------------------------------------------------------------------
    | Column Names
    |--------------------------------------------------------------------------
    | morph_type sets the key type for the polymorphic columns of
    | model_has_roles, model_has_scopes and az_direct_grants: 'int' (default),
    | 'ulid' or 'uuid'. Set it to match the primary-key type of the models that
    | get roles/scopes/grants (e.g. 'ulid' for ULID-keyed User/entities).
    | Any other value throws InvalidMorphTypeException at boot (fail-fast).
    */
    'column_names' => [
        'morph_type' => env('AZ_GUARD_MORPH_TYPE', 'int'),
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
    | Default Panel
    |--------------------------------------------------------------------------
    | Panel id used for authorization and permission resolution when no panel
    | is active on the current request (e.g. console commands, queued jobs, or
    | routes without the azguard.panel middleware). Leave null to refuse to
    | guess: with no active panel and more than one registered panel, checks
    | deny (fail-closed) rather than evaluate against an arbitrary panel.
    |
    | Note: the model permission APIs ($user->hasPermission() etc.) fall back to
    | the built-in 'app' panel when this is null — set it explicitly to change
    | that project-wide. The Gate/Authorizer path stays fail-closed as above.
    */
    'default_panel' => null,

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
    | Prune Expired Direct Grants
    |--------------------------------------------------------------------------
    | When true, AzGuard registers a daily scheduled task that runs
    | `guard:prune-grants` to delete expired direct grants. Off by default —
    | schedule the command yourself if you prefer explicit control. Expired
    | grants are never honoured regardless (the active() scope filters them);
    | pruning only keeps the table tidy.
    */
    'prune_expired_daily' => false,

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
