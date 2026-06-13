<?php

declare(strict_types=1);

namespace AzGuard\Filament\Resources\DirectGrantResource\Pages;

use AzGuard\AzGuardManager;
use AzGuard\Filament\Resources\DirectGrantResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * Страница создания Direct Grant.
 *
 * Переопределяет handleRecordCreation, чтобы:
 *  - выдать грант через AzGuardManager::forUser()->on()->ttl()->grant()
 *  - правильно заполнить grantable_type из конфигурации
 */
final class CreateDirectGrant extends CreateRecord
{
    protected static string $resource = DirectGrantResource::class;

    #[Override]
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Выдаём грант через AzGuardManager, а не прямым insert,
     * чтобы соблюдался тот же контракт что у grant() / GrantBuilder.
     */
    #[Override]
    protected function handleRecordCreation(array $data): Model
    {
        $userModel = config('auth.providers.users.model', 'App\\Models\\User');

        $user = $userModel::findOrFail($data['grantable_id']);

        $ttl = null;

        if (! empty($data['expires_at'])) {
            $expiresAt = Carbon::parse($data['expires_at']);
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
            ->grant($data['permission_key']);
    }
}
