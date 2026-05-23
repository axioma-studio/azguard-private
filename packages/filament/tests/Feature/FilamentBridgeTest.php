<?php

declare(strict_types=1);

use AzGuard\Filament\Concerns\AuthorizesAzGuardPermissions;

test('trait проверяет hasAzPermission', function () {
    $checker = new class
    {
        use AuthorizesAzGuardPermissions;

        public function check(string $permission): bool
        {
            return $this->userCanAzPermission(permission: $permission);
        }
    };

    expect($checker->check(permission: 'admin.access'))->toBeFalse();
});
