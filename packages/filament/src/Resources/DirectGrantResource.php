<?php

declare(strict_types=1);

namespace AzGuard\Filament\Resources;

use App\Models\User;
use AzGuard\AzGuardManager;
use AzGuard\Filament\Resources\DirectGrantResource\Pages\CreateDirectGrant;
use AzGuard\Filament\Resources\DirectGrantResource\Pages\ListDirectGrants;
use AzGuard\Models\DirectGrant;
use AzGuard\Registry\Contracts\PermissionCatalog;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Override;
use UnitEnum;

/**
 * Filament Resource для просмотра, создания и отзыва Direct Grants.
 *
 * Форма создания:
 *  1. Выбор пользователя (searchable)
 *  2. Выбор панели из зарегистрированных panels
 *  3. Выбор права из PermissionCatalog (реактивно фильтруется по панели)
 *  4. Дата истечения (необязательно — бессрочно)
 */
final class DirectGrantResource extends Resource
{
    protected static ?string $model = DirectGrant::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-key';

    protected static string|UnitEnum|null $navigationGroup = 'AzGuard';

    protected static ?string $label = 'Direct Grant';

    protected static ?string $pluralLabel = 'Direct Grants';

    // ─── Form ─────────────────────────────────────────────────────────────────

    #[Override]
    public static function form(Schema $schema): Schema
    {
        $userModel = config('auth.providers.users.model', User::class);
        $labelColumn = config('az-guard.filament.user_label_column', 'name');

        return $schema->components([

            // ── 1. Пользователь ──────────────────────────────────────────────
            Select::make('grantable_id')
                ->label('Пользователь')
                ->required()
                ->searchable()
                ->getSearchResultsUsing(
                    fn (string $search) => $userModel::where($labelColumn, 'like', "%{$search}%")
                        ->limit(50)
                        ->pluck($labelColumn, 'id')
                        ->toArray(),
                )
                ->getOptionLabelUsing(
                    fn ($value) => $userModel::find($value)?->{$labelColumn} ?? "#{$value}",
                )
                ->columnSpan('full'),

            // ── 2. Панель ────────────────────────────────────────────────────
            Select::make('panel_id')
                ->label('Панель')
                ->required()
                ->options(fn () => collect(app(AzGuardManager::class)->getPanels())
                    ->mapWithKeys(fn ($panel, $id) => [$id => $id])
                    ->toArray(),
                )
                ->live()
                ->afterStateUpdated(fn ($set) => $set('permission_key', null)),

            // ── 3. Право (реактивно по панели) ───────────────────────────────
            Select::make('permission_key')
                ->label('Право')
                ->required()
                ->options(function (Get $get): array {
                    $panelId = $get('panel_id');

                    if (! $panelId) {
                        return [];
                    }

                    /** @var PermissionCatalog $catalog */
                    $catalog = app(PermissionCatalog::class);
                    $options = [];

                    foreach ($catalog->groups($panelId) as $group => $definitions) {
                        foreach ($definitions as $def) {
                            $label = ($def->label() ?? $def->key());
                            $options[$group][$def->key()] = $label;
                        }
                    }

                    return $options;
                })
                ->searchable()
                ->disabled(fn (Get $get): bool => ! $get('panel_id'))
                ->helperText(fn (Get $get): string => $get('panel_id')
                    ? ''
                    : 'Сначала выберите панель.',
                ),

            // ── 4. Дата истечения ─────────────────────────────────────────────
            DateTimePicker::make('expires_at')
                ->label('Действует до')
                ->nullable()
                ->helperText('Оставьте пустым для бессрочного гранта.')
                ->minDate(now()->addMinute()),

        ])->columns(2);
    }

    // ─── Table ────────────────────────────────────────────────────────────────

    #[Override]
    public static function table(Table $table): Table
    {
        $userModel = config('auth.providers.users.model', User::class);
        $labelColumn = config('az-guard.filament.user_label_column', 'name');

        return $table
            ->columns([
                TextColumn::make('grantable_id')
                    ->label('Пользователь')
                    ->formatStateUsing(function (string $state, DirectGrant $record) use ($userModel, $labelColumn): string {
                        if ($record->grantable_type !== $userModel) {
                            return $record->grantable_type.'#'.$state;
                        }

                        $user = $userModel::find($state);

                        return $user?->{$labelColumn} ?? "#{$state}";
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

                Filter::make('active')
                    ->label('Только активные')
                    ->query(fn ($query) => $query->where(
                        fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()),
                    )),
            ])
            ->actions([
                DeleteAction::make()->label('Отозвать'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->label('Отозвать выбранные'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // ─── Pages ────────────────────────────────────────────────────────────────

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListDirectGrants::route('/'),
            'create' => CreateDirectGrant::route('/create'),
        ];
    }
}
