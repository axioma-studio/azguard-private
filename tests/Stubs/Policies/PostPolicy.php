<?php

namespace AzGuard\Tests\Stubs\Policies;

use App\Models\User;

class PostPolicy
{
    public function view(User $user): bool
    {
        // Проверяем именно префиксную версию
        return $user->hasAzPermission('admin.post.view');
    }

    public function create(User $user): bool
    {
        return $user->hasAzPermission('admin.post.create');
    }
}
