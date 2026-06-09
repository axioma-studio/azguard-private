<?php

declare(strict_types=1);

namespace AzGuard\Filament\Resources;

use AzGuard\Filament\Resources\RoleResource\Pages\CreateRole;
use AzGuard\Filament\Resources\RoleResource\Pages\EditRole;
use AzGuard\Filament\Resources\RoleResource\Pages\ListRoles;
use AzGuard\Models\Role;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Override;

/**
 * Filament Resource для управления DB-ролями.
 *
 * Поддерживает два режима:
 *
 *  «Custom role»  — права хранятся в az_guard_role_permissions,
 *                   редактируются через RolePermissionsRelationManager.
 *
 *  «Code role»    — права определяет PHP-класс (class_name != null);
 *                   вкладка «Права» скрыта (см. RolePermissionsRelationManager::canViewForRecord).
 */
final class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'AzGuard';

    protected static ?string $label = 'Роль';

    protected static ?string $pluralLabel = 'Роли';

    #[Override]
    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Название')
                ->required()
                ->maxLength(128)
                ->unique(ignoreRecord: true)
                ->columnSpan('full'),

            TextInput::make('level')
                ->label('Уровень приоритета')
                ->integer()
                ->default(0)
                ->minValue(0)
                ->helperText('Чем выше — тем больше приоритет при объединении прав.'),

            // ── Переключатель режима ─────────────────────────────────────────────
            Toggle::make('is_code_role')
                ->label('Управляется PHP-классом (code role)')
                ->helperText('Включите, если права роли определяет PHP-класс, а не БД.')
                ->default(false)
                ->live()
                ->afterStateHydrated(function (Toggle $component, $state, $record): void {
                    // При загрузке существующей записи — выставляем toggle по наличию class_name.
                    if ($record !== null) {
                        $component->state((bool) $record->class_name);
                    }
                })
                ->dehydrated(false) // не сохраняем в БД напрямую
                ->columnSpan('full'),

            // ── Code-role: поле класса ────────────────────────────────────────────
            TextInput::make('class_name')
                ->label('FQCN PHP-класса')
                ->placeholder('App\\Roles\\EditorRole')
                ->helperText('Полное имя класса, реализующего getAzPermissions().')
                ->nullable()
                ->visible(fn (Get $get): bool => (bool) $get('is_code_role'))
                ->columnSpan('full'),

            // ── Code-role: информационный placeholder ─────────────────────────────
            Placeholder::make('code_role_info')
                ->label('')
                ->content(fn (): HtmlString => new HtmlString(
                    '<div style="display:flex;align-items:center;gap:8px;padding:10px 14px;'
                    .'background:oklch(0.96 0.02 220);border:1px solid oklch(0.88 0.04 220);'
                    .'border-radius:6px;font-size:0.875rem;color:oklch(0.35 0.08 220);">'
                    .'<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" '
                    .'viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">'
                    .'<path stroke-linecap="round" stroke-linejoin="round" '
                    .'d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/>'
                    .'</svg>'
                    .'Права этой роли определяет PHP-класс. Вкладка «Права» в режиме редактирования скрыта.'
                    .'</div>'
                ))
                ->visible(fn (Get $get): bool => (bool) $get('is_code_role'))
                ->columnSpan('full'),
        ])->columns(2);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->width('60px'),

                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                // Тип роли: Code / Custom
                IconColumn::make('is_code_role')
                    ->label('Тип')
                    ->state(fn (Role $record): bool => $record->class_name !== null)
                    ->boolean()
                    ->trueIcon('heroicon-o-code-bracket')
                    ->falseIcon('heroicon-o-circle-stack')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->tooltip(fn (Role $record): string => $record->class_name !== null
                        ? 'Code role: '.$record->class_name
                        : 'Custom role: права из БД'
                    ),

                TextColumn::make('level')
                    ->label('Уровень')
                    ->sortable(),

                TextColumn::make('dbPermissions_count')
                    ->label('Прав (DB)')
                    ->counts('dbPermissions')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_code_role')
                    ->label('Тип роли')
                    ->placeholder('Все')
                    ->trueLabel('Code roles')
                    ->falseLabel('Custom roles')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('class_name'),
                        false: fn ($query) => $query->whereNull('class_name'),
                    ),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('level', 'desc');
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
