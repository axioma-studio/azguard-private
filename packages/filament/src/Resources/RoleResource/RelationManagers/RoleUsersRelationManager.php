<?php

declare(strict_types=1);

namespace AzGuard\Filament\Resources\RoleResource\RelationManagers;

use App\Models\User;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;

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

    #[Override]
    public function form(Schema $schema): Schema
    {
        $userModel = config('auth.providers.users.model', User::class);
        $labelColumn = config('az-guard.filament.user_label_column', 'name');

        return $schema->components([
            Select::make('id')
                ->label('Пользователь')
                ->options(fn () => $userModel::query()->pluck($labelColumn, 'id'))
                ->searchable()
                ->required(),
        ]);
    }

    #[Override]
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
                AttachAction::make()
                    ->label('Назначить роль')
                    ->preloadRecordSelect(),
            ])
            ->actions([
                DetachAction::make()->label('Отозвать'),
            ])
            ->bulkActions([
                DetachBulkAction::make()->label('Отозвать выбранных'),
            ]);
    }
}
