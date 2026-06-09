<?php

declare(strict_types=1);

namespace AzGuard\Filament\Resources\DirectGrantResource\Pages;

use App\Models\User;
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
 *  - выдать грант через AzGuardManager::forUser()->on()->ttl()->give()
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
     * чтобы соблюдался тот же контракт что у grantDirect() / GrantBuilder.
     */
    #[Override]
    protected function handleRecordCreation(array $data): Model
    {
        $userModel = config('auth.providers.users.model', User::class);

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
            ->give($data['permission_key']);
    }
}
