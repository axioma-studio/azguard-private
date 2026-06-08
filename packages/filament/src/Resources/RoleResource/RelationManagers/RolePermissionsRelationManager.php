<?php

declare(strict_types=1);

namespace AzGuard\Filament\Resources\RoleResource\RelationManagers;

use AzGuard\AzGuardManager;
use AzGuard\Models\RolePermission;
use AzGuard\Registry\Contracts\PermissionCatalog;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Relation Manager: права DB-роли.
 *
 * Права сгруппированы по группам из PermissionCatalog.
 * Для ролей с class_name права берутся из класса и недоступны для редактирования через UI.
 */
final class RolePermissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'dbPermissions';

    protected static ?string $title = 'Права';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        // Для PHP-класс-ролей права определяет класс — скрываем вкладку.
        return $ownerRecord->class_name === null;
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            TextColumn::make('permission_key')->label('Право'),
            TextColumn::make('panel_id')->label('Панель'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('permission_key')
            ->columns([
                TextColumn::make('panel_id')
                    ->label('Панель')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('permission_key')
                    ->label('Ключ права')
                    ->searchable(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('sync_permissions')
                    ->label('Редактировать права')
                    ->icon('heroicon-o-pencil-square')
                    ->form(fn () => $this->buildPermissionsForm())
                    ->fillForm(fn () => $this->currentPermissionsFormData())
                    ->action(fn (array $data) => $this->syncPermissions($data)),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()->label('Отзыв'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('Отзыв выбранных'),
            ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Строит форму выбора прав: каждая панель — своя CheckboxList,
     * сгруппированная по группам из каталога.
     */
    private function buildPermissionsForm(): array
    {
        /** @var PermissionCatalog $catalog */
        $catalog = app(PermissionCatalog::class);
        /** @var AzGuardManager $manager */
        $manager = app(AzGuardManager::class);

        $sections = [];

        foreach (array_keys($manager->getPanels()) as $panelId) {
            $groups = $catalog->groups($panelId);

            if ($groups === []) {
                continue;
            }

            $checkboxLists = [];

            foreach ($groups as $groupName => $definitions) {
                $options = [];

                foreach ($definitions as $definition) {
                    $options[$definition->key()] = $definition->label() ?? $definition->key();
                }

                $checkboxLists[] = CheckboxList::make("permissions.{$panelId}.{$groupName}")
                    ->label($groupName)
                    ->options($options)
                    ->columns(2)
                    ->gridDirection('row');
            }

            $sections[] = Section::make($panelId)
                ->heading(«Панель: » . $panelId)
                ->schema($checkboxLists)
                ->collapsible();
        }

        return $sections;
    }

    /**
     * Заполняет форму текущими значениями.
     */
    private function currentPermissionsFormData(): array
    {
        /** @var PermissionCatalog $catalog */
        $catalog = app(PermissionCatalog::class);
        /** @var AzGuardManager $manager */
        $manager = app(AzGuardManager::class);

        $role = $this->getOwnerRecord();
        $existing = $role->dbPermissions()->get()->groupBy('panel_id');

        $data = ['permissions' => []];

        foreach (array_keys($manager->getPanels()) as $panelId) {
            $groups = $catalog->groups($panelId);
            $granted = $existing->get($panelId, collect())->pluck('permission_key')->flip();

            foreach ($groups as $groupName => $definitions) {
                $checked = [];

                foreach ($definitions as $definition) {
                    if ($granted->has($definition->key())) {
                        $checked[] = $definition->key();
                    }
                }

                $data['permissions'][$panelId][$groupName] = $checked;
            }
        }

        return $data;
    }

    /**
     * Синхронизирует DB-права роли: удаляет старые, добавляет новые.
     */
    private function syncPermissions(array $data): void
    {
        $role = $this->getOwnerRecord();
        $permissionsData = $data['permissions'] ?? [];

        // Собираем все ключи в формате panelId => [keys]
        $desired = [];

        foreach ($permissionsData as $panelId => $groups) {
            foreach ($groups as $keys) {
                foreach ($keys as $key) {
                    $desired[$panelId][] = $key;
                }
            }
        }

        // Удаляем всё, затем upsert
        $role->dbPermissions()->delete();

        $rows = [];
        $now = now();

        foreach ($desired as $panelId => $keys) {
            foreach (array_unique($keys) as $key) {
                $rows[] = [
                    'role_id'        => $role->id,
                    'permission_key' => $key,
                    'panel_id'       => $panelId,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }
        }

        if ($rows !== []) {
            RolePermission::insert($rows);
        }
    }
}
