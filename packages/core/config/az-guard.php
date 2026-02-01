<?php

return [
    'models' => ['role' => \AzGuard\Models\Role::class, 'scope' => \AzGuard\Models\ModelHasScope::class],
    'table_names' => ['roles' => 'roles', 'model_has_roles' => 'model_has_roles', 'model_has_scopes' => 'model_has_scopes'],
];
