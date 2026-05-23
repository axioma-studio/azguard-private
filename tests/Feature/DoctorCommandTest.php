<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('guard:doctor проходит для тестовой панели', function (): void {
    $this->artisan(command: 'guard:doctor', parameters: ['--panel' => 'test'])
        ->assertSuccessful();
});

it('guard:doctor находит дубликат ability', function (): void {
    $duplicatePath = __DIR__.'/../Stubs/Posts/Policies/DuplicatePostPolicy.php';

    File::put(path: $duplicatePath, contents: <<<'PHP'
<?php

declare(strict_types=1);

namespace AzGuard\Tests\Stubs\Posts\Policies;

use AzGuard\Attributes\GateAbility;
use AzGuard\Tests\Stubs\Posts\Permissions\PostPermission;
use AzGuard\Tests\Stubs\User;

final class DuplicatePostPolicy
{
    #[GateAbility(permission: PostPermission::View)]
    public function canViewAgain(User $user): bool
    {
        return true;
    }
}
PHP);

    try {
        $this->artisan(command: 'guard:doctor', parameters: ['--panel' => 'test'])
            ->assertFailed();
    } finally {
        if (File::exists(path: $duplicatePath)) {
            File::delete(paths: $duplicatePath);
        }
    }
});
