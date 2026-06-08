<?php

declare(strict_types=1);

namespace AzGuard\Filament\Resources;

use AzGuard\Filament\Resources\RoleResource\Pages\CreateRole;
use AzGuard\Filament\Resources\RoleResource\Pages\EditRole;
use AzGuard\Filament\Resources\RoleResource\Pages\ListRoles;
use AzGuard\Models\Role;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Filament Resource для управления DB-ролями.
 *
 * Отображает: список ролей, создание, редактирование.
 * На странице EditRole расположены две вкладки:
 * «Права» (дерево чекбоксов) и «Пользователи».
 */
final class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'AzGuard';

    protected static ?string $label = 'Роль';

    protected static ?string $pluralLabel = 'Роли';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Название')
                ->required()
                ->maxLength(128)
                ->unique(ignoreRecord: true),

            TextInput::make('level')
                ->label('Уровень')
                ->integer()
                ->default(0)
                ->minValue(0)
                ->helperText('Чем выше — больше приоритет при объединении прав.'),

            TextInput::make('class_name')
                ->label('PHP-класс логики')
                ->nullable()
                ->placeholder('App\\Roles\\EditorRole')
                ->helperText('Опционально. Если заполнено — права берутся из класса, а не из БД.'),
        ]);
    }

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

                TextColumn::make('level')
                    ->label('Уровень')
                    ->sortable(),

                TextColumn::make('class_name')
                    ->label('PHP-класс')
                    ->placeholder('— DB-роль —')
                    ->toggleable(),

                TextColumn::make('dbPermissions_count')
                    ->label('Прав')
                    ->counts('dbPermissions')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('level', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit'   => EditRole::route('/{record}/edit'),
        ];
    }
}
