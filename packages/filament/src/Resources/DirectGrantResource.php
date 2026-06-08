<?php

declare(strict_types=1);

namespace AzGuard\Filament\Resources;

use AzGuard\Filament\Resources\DirectGrantResource\Pages\ListDirectGrants;
use AzGuard\Models\DirectGrant;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Filament Resource для просмотра и отзыва Direct Grants.
 *
 * Создание грантов через UI не предусмотрено —
 * для этого есть AzGuardManager::grantDirect() и artisan az:grant.
 */
final class DirectGrantResource extends Resource
{
    protected static ?string $model = DirectGrant::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'AzGuard';

    protected static ?string $label = 'Direct Grant';

    protected static ?string $pluralLabel = 'Direct Grants';

    public static function table(Table $table): Table
    {
        $userModel   = config('auth.providers.users.model', \App\Models\User::class);
        $labelColumn = config('az-guard.filament.user_label_column', 'name');

        return $table
            ->columns([
                TextColumn::make('model_id')
                    ->label('Пользователь')
                    ->formatStateUsing(function ($state, DirectGrant $record) use ($userModel, $labelColumn): string {
                        if ($record->model_type !== $userModel) {
                            return $record->model_type . '#' . $state;
                        }

                        $user = $userModel::find($state);

                        return $user?->{$labelColumn} ?? '#{$state}';
                    })
                    ->searchable(),

                TextColumn::make('panel_id')
                    ->label('Панель')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('permission_key')
                    ->label('Право')
                    ->searchable(),

                TextColumn::make('expires_at')
                    ->label('Действует до')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Бессрочно')
                    ->color(fn (DirectGrant $record): string => $record->isExpired() ? 'danger' : 'success')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Выдан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('panel_id')
                    ->label('Панель')
                    ->options(fn () => DirectGrant::query()->distinct()->pluck('panel_id', 'panel_id')),

                Tables\Filters\Filter::make('active')
                    ->label('Только активные')
                    ->query(fn ($query) => $query->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()->label('Отозвать'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('Отозвать выбранные'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDirectGrants::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
