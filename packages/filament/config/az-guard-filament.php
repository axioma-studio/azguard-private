<?php

declare(strict_types=1);

use Filament\Resources\Pages\CreateRecord;

return [
    /*
    |--------------------------------------------------------------------------
    | Linked AzGuard panel
    |--------------------------------------------------------------------------
    |
    | The AzGuard panel id whose permission catalog backs this Filament panel.
    | Discovered Filament resources/pages register their permissions into this
    | panel, and resource authorization is checked against it.
    |
    */
    'panel' => 'admin',

    /*
    |--------------------------------------------------------------------------
    | Enforcement
    |--------------------------------------------------------------------------
    |
    | When true, the plugin authorizes every discovered resource against its
    | generated permission with zero code in the resources: it disables
    | Filament's policy-existence shortcut and answers resource Gate checks
    | from the user's AzGuard permissions. Set false to manage authorization
    | yourself (e.g. with generated policies).
    |
    */
    'enforce' => true,

    /*
    |--------------------------------------------------------------------------
    | Permission source
    |--------------------------------------------------------------------------
    |
    | Where the generated permission *definitions* live:
    |   'database' — keys are registered in the catalog at runtime and roles map
    |                to them via the DB (edit access in the Role UI). No files.
    |   'enum'     — `azguard:filament:generate` writes a permission enum per
    |                resource (typed, IDE-friendly). Physical files, picked up
    |                by the panel's EnumPermissionCatalogBuilder.
    |
    | ('policy' generation — policies with #[GateAbility] — is planned.)
    |
    */
    'source' => 'database',

    /*
    |--------------------------------------------------------------------------
    | Abilities generated per resource
    |--------------------------------------------------------------------------
    |
    | Each ability becomes its own permission key. Trim this list to scope
    | down, or add custom abilities.
    |
    */
    'abilities' => [
        'view_any',
        'view',
        'create',
        'update',
        'delete',
        'restore',
        'force_delete',
        'replicate',
        'reorder',
    ],

    'pages' => [
        'ability' => 'view',
    ],

    'widgets' => [
        'ability' => 'view',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission key scheme
    |--------------------------------------------------------------------------
    |
    | Template for a resolved permission key. Placeholders: {panel}, {resource},
    | {ability}. `case` formats the {resource} segment (snake|kebab|camel|none).
    |
    */
    'key' => '{panel}.{resource}.{ability}',
    'case' => 'snake',

    /*
    |--------------------------------------------------------------------------
    | Discovery exclusions
    |--------------------------------------------------------------------------
    |
    | Fully-qualified resource/page/widget class names to skip during
    | discovery and generation.
    |
    */
    'exclude' => [
        'resources' => [],
        'pages' => [
            CreateRecord::class,
        ],
        'widgets' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Super admin
    |--------------------------------------------------------------------------
    |
    | Role name that bypasses every resource check (null = rely on the panel's
    | wildcard role, e.g. SuperAdminRole).
    |
    */
    'super_admin' => null,

    /*
    |--------------------------------------------------------------------------
    | Code generation targets (source = enum | policy)
    |--------------------------------------------------------------------------
    */
    'generation' => [
        'enum_namespace' => 'App\\Guards\\Admin\\Permissions',
        'enum_path' => 'app/Guards/Admin/Permissions',
        'policy_namespace' => 'App\\Policies',
        'policy_path' => 'app/Policies',
    ],
];
