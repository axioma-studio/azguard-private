<?php

declare(strict_types=1);

namespace AzGuard\Filament\Pages;

use AzGuard\Filament\AzGuardPlugin;
use AzGuard\Guard\AzGuardDiagnostics;
use BackedEnum;
use Filament\Pages\Page;
use Override;
use Throwable;
use UnitEnum;

/**
 * Страница диагностики AzGuard в Filament UI.
 *
 * Отображает результат AzGuardDiagnostics::diagnose() в трёх секциях:
 *  - Abilities  — зарегистрированные Gate abilities (таблица)
 *  - Warnings   — предупреждения (жёлтый)
 *  - Errors     — ошибки согласованности (красный)
 *
 * Фильтрует по panelId, указанному в AzGuardPlugin::forPanel().
 */
final class DoctorPage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-stethoscope';

    protected static string|UnitEnum|null $navigationGroup = 'AzGuard';

    protected static ?string $navigationLabel = 'Doctor';

    protected static ?string $title = 'AzGuard Doctor';

    protected string $view = 'az-guard::pages.doctor';

    // ─── Badge: кол-во ошибок в навигации ─────────────────────────────────

    #[Override]
    public static function getNavigationBadge(): ?string
    {
        $result = self::runDiagnose();
        $errorCount = count($result['errors']);

        return $errorCount > 0 ? (string) $errorCount : null;
    }

    #[Override]
    public static function getNavigationBadgeColor(): string
    {
        $result = self::runDiagnose();

        if (count($result['errors']) > 0) {
            return 'danger';
        }

        if (count($result['warnings']) > 0) {
            return 'warning';
        }

        return 'success';
    }

    // ─── View data ─────────────────────────────────────────────────────────────

    public function getDiagnoseResult(): array
    {
        return self::runDiagnose();
    }

    // ─── Internal ───────────────────────────────────────────────────────────

    /**
     * @return array{errors: list<string>, warnings: list<string>, abilities: list<array{panel: string, ability: string, handler: string}>}
     */
    private static function runDiagnose(): array
    {
        /** @var AzGuardDiagnostics $doctor */
        $doctor = app(AzGuardDiagnostics::class);

        // Фильтруем по panelId из плагина, если он зарегистрирован
        $panelFilter = null;

        try {
            /** @var AzGuardPlugin $plugin */
            $plugin = filament()->getCurrentPanel()?->getPlugin('az-guard');
            $panelFilter = $plugin->getPanelId();
        } catch (Throwable) {
            // Плагин недоступен — диагностируем все панели
        }

        return $doctor->diagnose(panelFilter: $panelFilter);
    }
}
