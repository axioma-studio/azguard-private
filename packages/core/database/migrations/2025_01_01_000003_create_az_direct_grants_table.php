<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('az-guard.table_names.direct_grants', 'az_direct_grants');

        Schema::create((string) $tableName, function (Blueprint $table): void {
            $table->id();

            // Polymorphic owner (User, Admin, …)
            $table->morphs('grantable');

            $table->string('panel_id');       // 'app', 'admin', …
            $table->string('permission_key'); // 'app.documents.export'

            $table->timestamp('expires_at')->nullable(); // null = бессрочно

            $table->timestamps();

            // Каждый пользователь получает конкретное право в панели только один раз
            $table->unique(
                columns: ['grantable_type', 'grantable_id', 'panel_id', 'permission_key'],
                name: 'az_direct_grants_unique',
            );
        });
    }

    public function down(): void
    {
        $tableName = config('az-guard.table_names.direct_grants', 'az_direct_grants');
        Schema::dropIfExists((string) $tableName);
    }
};
