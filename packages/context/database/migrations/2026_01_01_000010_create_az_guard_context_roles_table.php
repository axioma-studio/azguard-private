<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица контекстных ролей: rights scoped to context (workspace, project, etc.)
 *
 * Строка = «пользователь X в контексте (type=workspace, id=42) панели app
 *           имеет право app.posts.edit»
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('az_guard_context_roles', function (Blueprint $table): void {
            $table->id();

            // Пользователь (polymorphic)
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');

            // Контекст
            $table->string('context_type');  // 'workspace', 'project', ...
            $table->string('context_id');    // string чтобы поддерживать UUID и int

            // Право
            $table->string('panel_id');
            $table->string('permission_key');

            $table->timestamps();

            // Уникальность: один пользователь не получает одно право дважды
            $table->unique(
                ['model_type', 'model_id', 'context_type', 'context_id', 'panel_id', 'permission_key'],
                'az_ctx_roles_unique',
            );

            // Индексы для быстрого поиска
            $table->index(['model_type', 'model_id', 'panel_id'], 'az_ctx_roles_user_panel');
            $table->index(['context_type', 'context_id'], 'az_ctx_roles_context');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('az_guard_context_roles');
    }
};
