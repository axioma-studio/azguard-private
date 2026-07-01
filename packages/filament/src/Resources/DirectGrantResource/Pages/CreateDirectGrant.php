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
 * Direct Grant creation page.
 *
 * Overrides handleRecordCreation to:
 *  - issue the grant via AzGuardManager::forUser()->on()->ttl()->grant()
 *  - correctly populate grantable_type from configuration
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
     * Issue the grant via AzGuardManager rather than a direct insert,
     * so the same contract as grant() / GrantBuilder is honored.
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
                $ttl = null; // invalid value — no expiry
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
