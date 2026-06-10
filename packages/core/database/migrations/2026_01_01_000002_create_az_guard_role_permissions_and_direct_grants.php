<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Создаёт таблицы для Phases 3 системы grants:
 *
 * az_guard_role_permissions — пермиссии, назначенные DB-роли (не PHP-классы)
 * az_guard_direct_grants   — пермиссии напрямую пользователю (без роли), с TTL
 */
return new class extends Migration
{
    public function up(): void
    {
        $t = config('az-guard.table_names');

        // Пермиссии DB-ролей (роли без class_name, или custom DB-роли)
        Schema::create($t['role_permissions'] ?? 'az_guard_role_permissions', function (Blueprint $table) use ($t) {
            $table->id();
            $table->foreignId('role_id')
                ->constrained($t['roles'])
                ->cascadeOnDelete();
            $table->string('permission_key');       // resolved key: "app.documents.view"
            $table->string('panel_id');             // "app", "admin"
            $table->timestamps();

            $table->unique(['role_id', 'permission_key', 'panel_id'], 'az_role_perm_unique');
            $table->index(['panel_id', 'permission_key'], 'az_role_perm_lookup');
        });

        // Прямые grants пользователю (без роли)
        Schema::create($t['direct_grants'] ?? 'az_guard_direct_grants', function (Blueprint $table) {
            $table->id();
            $table->morphs('grantable');            // пользователь (User или любая модель)
            $table->string('permission_key');       // resolved key
            $table->string('panel_id');             // "app"
            $table->timestamp('expires_at')->nullable(); // null = бессрочно
            $table->timestamps();

            $table->unique(
                ['grantable_type', 'grantable_id', 'permission_key', 'panel_id'],
                'az_direct_grant_unique',
            );
            $table->index(['grantable_type', 'grantable_id', 'panel_id'], 'az_direct_grant_lookup');
        });
    }

    public function down(): void
    {
        $t = config('az-guard.table_names');

        Schema::dropIfExists($t['direct_grants'] ?? 'az_guard_direct_grants');
        Schema::dropIfExists($t['role_permissions'] ?? 'az_guard_role_permissions');
    }
};
