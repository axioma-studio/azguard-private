<?php

declare(strict_types=1);

namespace AzGuard\Filament\Resources\RoleResource\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Relation Manager: пользователи DB-роли.
 *
 * Позволяет присваивать / отзывать роль пользователям.
 * Использует morph-связь через Role::users().
 */
final class RoleUsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Пользователи';

    public function form(Form $form): Form
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        $labelColumn = config('az-guard.filament.user_label_column', 'name');

        return $form->schema([
            Select::make('id')
                ->label('Пользователь')
                ->options(fn () => $userModel::query()->pluck($labelColumn, 'id'))
                ->searchable()
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        $labelColumn = config('az-guard.filament.user_label_column', 'name');

        return $table
            ->recordTitleAttribute($labelColumn)
            ->columns([
                TextColumn::make('id')->label('ID')->width('60px'),
                TextColumn::make($labelColumn)->label('Пользователь')->searchable(),
                TextColumn::make('email')->label('Email')->searchable()->toggleable(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Назначить роль')
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()->label('Отозвать'),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make()->label('Отозвать выбранных'),
            ]);
    }
}
