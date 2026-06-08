<?php

declare(strict_types=1);

namespace AzGuard\Contracts;

use AzGuard\Support\Panel;
use Closure;

/**
 * Контракт для AzGuardManager.
 * Используйте этот интерфейс в type-hint'ах для тестируемости через mock.
 */
interface AzGuardManagerInterface
{
    /**
     * Регистрирует панель через замыкание.
     */
    public function registerPanel(Closure $panel): void;

    /**
     * Возвращает все зарегистрированные панели.
     *
     * @return array<string, Panel>
     */
    public function getPanels(): array;

    /**
     * Возвращает панель по идентификатору или null.
     */
    public function panel(string $id): ?Panel;

    /**
     * Возвращает текущую активную панель.
     */
    public function currentPanel(): ?Panel;

    /**
     * Устанавливает текущую активную панель.
     */
    public function setCurrentPanel(?Panel $panel): void;

    /**
     * Разрешает полное имя разрешения для панели.
     *
     * @throws \RuntimeException если панель не зарегистрирована
     */
    public function permission(string $panelId, string|\UnitEnum $permission): string;
}
