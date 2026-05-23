<?php

return [
    'models' => [
        'role' => \AzGuard\Models\Role::class,
        'scope' => \AzGuard\Models\ModelHasScope::class,
    ],
    'models_namespace' => 'App\\Models\\',
    'table_names' => [
        'roles' => 'roles',
        'model_has_roles' => 'model_has_roles',
        'model_has_scopes' => 'model_has_scopes',
    ],
    'panels' => [],
    'middleware' => [
        'check_access_alias' => 'check.access',
    ],
];
