<?php

declare(strict_types=1);

namespace AzGuard\Filament\Resources\DirectGrantResource\Pages;

use AzGuard\AzGuardManager;
use AzGuard\Filament\Resources\DirectGrantResource;
use AzGuard\Models\DirectGrant;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Страница создания Direct Grant.
 *
 * Переопределяет handleRecordCreation, чтобы:
 *  - выдать грант через AzGuardManager::forUser()->on()->ttl()->give()
 *  - правильно заполнить grantable_type из конфигурации
 */
final class CreateDirectGrant extends CreateRecord
{
    protected static string $resource = DirectGrantResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Выдаём грант через AzGuardManager, а не прямым insert,
     * чтобы соблюдался тот же контракт что у grantDirect() / GrantBuilder.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);

        $user = $userModel::findOrFail($data['grantable_id']);

        $ttl = null;

        if (! empty($data['expires_at'])) {
            $expiresAt = \Illuminate\Support\Carbon::parse($data['expires_at']);
            $ttl = (int) now()->diffInSeconds($expiresAt, absolute: false);

            if ($ttl <= 0) {
                $ttl = null; // некорректное значение — бессрочно
            }
        }

        /** @var AzGuardManager $manager */
        $manager = app(AzGuardManager::class);

        return $manager
            ->forUser($user)
            ->on($data['panel_id'])
            ->ttl($ttl)
            ->give($data['permission_key']);
    }
}
