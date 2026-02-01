<?php

namespace AzGuard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Role extends Model
{
    // Добавляем class_name, чтобы знать, какой файл Roles/*.php за это отвечает
    protected $fillable = ['name', 'level', 'class_name'];

    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            config('auth.providers.users.model'),
            'model',
            config('az-guard.table_names.model_has_roles')
        );
    }

    /**
     * Инстанцирует класс логики роли (например, SuperAdminRole).
     */
    public function getRoleLogic(): ?object
    {
        if ($this->class_name && class_exists($this->class_name)) {
            return new $this->class_name();
        }

        return null;
    }
}
