<?php

declare(strict_types=1);

namespace AzGuard\Support;

/**
 * Centralized config accessor for AzGuard.
 *
 * Replaces scattered config('az-guard.*') calls with typed static methods.
 * Inspired by spatie/laravel-permission Support\Config.
 *
 * Usage:
 *   Config::roleModel()          // 'AzGuard\Models\Role'
 *   Config::tableName('roles')   // 'roles'
 *   Config::isEnabled('teams')   // false
 */
final class Config
{
    // ─── Models ───────────────────────────────────────────────────────────────

    public static function roleModel(): string
    {
        return (string) config('az-guard.models.role', \AzGuard\Models\Role::class);
    }

    public static function scopeModel(): string
    {
        return (string) config('az-guard.models.scope', \AzGuard\Models\ModelHasScope::class);
    }

    public static function directGrantModel(): string
    {
        return (string) config('az-guard.models.direct_grant', \AzGuard\Models\DirectGrant::class);
    }

    public static function modelsNamespace(): string
    {
        return (string) config('az-guard.models_namespace', 'App\\Models\\');
    }

    // ─── Tables ───────────────────────────────────────────────────────────────

    public static function rolesTable(): string
    {
        return (string) config('az-guard.table_names.roles', 'roles');
    }

    public static function modelHasRolesTable(): string
    {
        return (string) config('az-guard.table_names.model_has_roles', 'model_has_roles');
    }

    public static function modelHasScopesTable(): string
    {
        return (string) config('az-guard.table_names.model_has_scopes', 'model_has_scopes');
    }

    public static function directGrantsTable(): string
    {
        return (string) config('az-guard.table_names.direct_grants', 'az_direct_grants');
    }

    /** @param 'roles'|'model_has_roles'|'model_has_scopes'|'direct_grants' $key */
    public static function tableName(string $key): string
    {
        return (string) config("az-guard.table_names.{$key}");
    }

    // ─── Columns ──────────────────────────────────────────────────────────────

    public static function rolePivotKey(): ?string
    {
        $value = config('az-guard.column_names.role_pivot_key');

        return $value !== null ? (string) $value : null;
    }

    public static function modelMorphKey(): string
    {
        return (string) config('az-guard.column_names.model_morph_key', 'model_id');
    }

    // ─── Features ─────────────────────────────────────────────────────────────

    public static function isEnabled(string $feature): bool
    {
        return (bool) config("az-guard.features.{$feature}", false);
    }

    public static function teamsEnabled(): bool
    {
        return static::isEnabled('teams');
    }

    public static function wildcardEnabled(): bool
    {
        return static::isEnabled('wildcard_permission');
    }

    public static function directGrantsEnabled(): bool
    {
        return static::isEnabled('direct_grants');
    }

    public static function auditLogEnabled(): bool
    {
        return static::isEnabled('audit_log');
    }

    // ─── Teams ────────────────────────────────────────────────────────────────

    public static function teamForeignKey(): string
    {
        return (string) config('az-guard.teams.foreign_key', 'team_id');
    }

    // ─── Cache ────────────────────────────────────────────────────────────────

    public static function cacheStore(): string
    {
        return (string) config('az-guard.cache.store', 'array');
    }

    /**
     * Cache TTL in seconds. Returns null for infinite cache.
     * Alias: cacheExpiration() — both names are supported.
     */
    public static function cacheTtl(): ?int
    {
        $value = config('az-guard.cache.expiration_time', 3600);

        return $value !== null ? (int) $value : null;
    }

    /** @alias cacheTtl() */
    public static function cacheExpiration(): ?int
    {
        return static::cacheTtl();
    }

    public static function cacheKey(): string
    {
        return (string) config('az-guard.cache.key', 'azguard.permissions');
    }

    // ─── Middleware ───────────────────────────────────────────────────────────

    public static function checkAccessAlias(): string
    {
        return (string) config('az-guard.middleware.check_access_alias', 'check.access');
    }

    // ─── Panels ───────────────────────────────────────────────────────────────

    /** @return array<string> */
    public static function panels(): array
    {
        return (array) config('az-guard.panels', []);
    }
}
