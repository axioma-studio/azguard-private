<?php

declare(strict_types=1);

namespace AzGuard;

use AzGuard\Contracts\AzGuardManagerInterface;
use AzGuard\Grants\GrantBuilder;
use AzGuard\Models\DirectGrant;
use AzGuard\Registry\Contracts\GrantSource;
use AzGuard\Support\Panel;
use AzGuard\Support\PanelResolver;
use BackedEnum;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Override;
use RuntimeException;
use UnitEnum;

final class AzGuardManager implements AzGuardManagerInterface
{
    /**
     * Container tag collected by EffectivePermissionResolver. Bind and tag a
     * custom GrantSource with this to plug it into the resolution chain.
     */
    public const string GRANT_SOURCES_TAG = 'azguard.grant_sources';

    /** @var array<string, Panel> */
    protected array $panels = [];

    protected ?Panel $currentPanel = null;

    // ─── Panels ──────────────────────────────────────────────────────────────

    #[Override]
    public function registerPanel(Panel|callable $panel): void
    {
        $panelInstance = $panel instanceof Panel ? $panel : $panel();
        $this->panels[$panelInstance->getId()] = $panelInstance;
    }

    /**
     * @return array<string, Panel>
     */
    #[Override]
    public function getPanels(): array
    {
        return $this->panels;
    }

    #[Override]
    public function panel(string|BackedEnum $id): ?Panel
    {
        return $this->panels[PanelResolver::normalizeId($id)] ?? null;
    }

    #[Override]
    public function currentPanel(): ?Panel
    {
        return $this->currentPanel;
    }

    #[Override]
    public function setCurrentPanel(?Panel $panel): void
    {
        $this->currentPanel = $panel;
    }

    #[Override]
    public function permission(string|BackedEnum $panelId, string|UnitEnum $permission): string
    {
        $panel = $this->panel(id: $panelId);

        if (! $panel instanceof Panel) {
            $id = PanelResolver::normalizeId($panelId);

            throw new RuntimeException("AzGuard panel [{$id}] is not registered.");
        }

        return $panel->resolvePermission(permission: $permission);
    }

    #[Override]
    public function tryPermission(string|BackedEnum $panelId, string|UnitEnum $permission): ?string
    {
        $panel = $this->panel(id: $panelId);

        return $panel?->resolvePermission(permission: $permission);
    }

    // ─── Extensions ───────────────────────────────────────────────────────────

    /**
     * Register a custom GrantSource. Bind it (singleton/scoped) if it is not
     * already bound, then tag it so EffectivePermissionResolver picks it up.
     *
     * Call this from a service provider's register() method:
     *   AzGuard::registerGrantSource(MyGrantSource::class);
     *
     * @param  class-string<GrantSource>  $sourceClass
     */
    #[Override]
    public function registerGrantSource(string $sourceClass): void
    {
        if (! app()->bound($sourceClass)) {
            app()->scoped($sourceClass);
        }

        app()->tag([$sourceClass], self::GRANT_SOURCES_TAG);
    }

    // ─── Grants API ─────────────────────────────────────────────────────────

    /**
     * Return a fluent GrantBuilder for a user.
     *
     * Example:
     *   AzGuard::forUser($user)->on('app')->ttl(3600)->grant('app.x.view');
     */
    #[Override]
    public function forUser(Authenticatable $user): GrantBuilder
    {
        return new GrantBuilder(user: $user);
    }

    /**
     * Shorthand: issue a direct grant.
     *
     * @param  int|null  $ttl  TTL in seconds. null = permanent.
     */
    #[Override]
    public function grant(
        Authenticatable $user,
        string|UnitEnum $permissionKey,
        string|BackedEnum $panelId = 'app',
        ?int $ttl = null,
    ): DirectGrant {
        return $this->forUser($user)->on($panelId)->ttl($ttl)->grant($permissionKey);
    }

    /**
     * Shorthand: revoke a direct grant.
     *
     * @return int Number of deleted records.
     */
    #[Override]
    public function revoke(
        Authenticatable $user,
        string|UnitEnum $permissionKey,
        string|BackedEnum $panelId = 'app',
    ): int {
        return $this->forUser($user)->on($panelId)->revoke($permissionKey);
    }

    /**
     * Shorthand: list active direct grants for a user in a panel.
     *
     * @return Collection<int, DirectGrant>
     */
    #[Override]
    public function grants(
        Authenticatable $user,
        string|BackedEnum $panelId = 'app',
    ): Collection {
        return $this->forUser($user)->on($panelId)->grants();
    }
}
