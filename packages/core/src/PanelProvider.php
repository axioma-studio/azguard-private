<?php

declare(strict_types=1);

namespace AzGuard;

use AzGuard\Auth\PolicyAttributeRegistrar;
use AzGuard\Facades\AzGuard;
use AzGuard\Guard\PolicyDiscovery;
use AzGuard\Registry\Builders\EnumPermissionCatalogBuilder;
use AzGuard\Registry\Builders\PolicyAbilityCatalogBuilder;
use AzGuard\Support\Panel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

abstract class PanelProvider extends ServiceProvider
{
    private ?Panel $cachedPanel = null;

    abstract public function panel(Panel $panel): Panel;

    public function register(): void
    {
        AzGuard::registerPanel(function (): Panel {
            $panel = $this->getPanel();
            $reflection = new ReflectionClass($this);
            $panel->setBasePath(basePath: dirname(path: $reflection->getFileName()));

            return $panel;
        });
    }

    public function boot(): void
    {
        $panel = $this->getPanel();
        $reflection = new ReflectionClass($this);
        $basePath = dirname(path: $reflection->getFileName());
        $baseNamespace = $reflection->getNamespaceName();

        $discovery = new PolicyDiscovery;
        $policyClasses = $discovery->discoverPolicyClasses(
            basePath: $basePath,
            baseNamespace: $baseNamespace,
        );

        app(PolicyAttributeRegistrar::class)->register(
            policyClasses: $policyClasses,
            panel: $panel,
        );

        foreach ($policyClasses as $policyClass) {
            $modelClass = $discovery->resolveModelClass(
                policyClass: $policyClass,
                basePath: $basePath,
                baseNamespace: $baseNamespace,
            );

            if ($modelClass !== null) {
                Gate::policy($modelClass, $policyClass);
            }
        }

        $this->registerCatalogBuilders(
            panel: $panel,
            policyClasses: $policyClasses,
            basePath: $basePath,
            baseNamespace: $baseNamespace,
        );
    }

    /**
     * Регистрирует EnumPermissionCatalogBuilder и PolicyAbilityCatalogBuilder
     * для данной панели в теге azguard.catalog_builders, чтобы
     * CompositePermissionCatalog мог собрать полный каталог прав.
     *
     * Можно переопределить в конкретном PanelProvider для добавления
     * собственных builders или замены стандартных.
     *
     * @param list<string> $policyClasses
     */
    protected function registerCatalogBuilders(
        Panel $panel,
        array $policyClasses,
        string $basePath,
        string $baseNamespace,
    ): void {
        $panelId = $panel->getId();
        $permissionEnums = $panel->getPermissionEnums();

        if ($permissionEnums !== []) {
            $this->app->tag([
                $this->app->instance(
                    'azguard.catalog_builder.' . $panelId . '.enum',
                    new EnumPermissionCatalogBuilder(
                        panelId: $panelId,
                        enumClasses: $permissionEnums,
                    ),
                ),
            ], 'azguard.catalog_builders');
        }

        if ($policyClasses !== []) {
            $this->app->tag([
                $this->app->instance(
                    'azguard.catalog_builder.' . $panelId . '.policy',
                    new PolicyAbilityCatalogBuilder(
                        panelId: $panelId,
                        policyClasses: $policyClasses,
                    ),
                ),
            ], 'azguard.catalog_builders');
        }
    }

    protected function getPanel(): Panel
    {
        if ($this->cachedPanel === null) {
            $reflection = new ReflectionClass($this);
            $this->cachedPanel = $this->panel(panel: Panel::make());
            $this->cachedPanel->setNamespace(namespace: $reflection->getNamespaceName());
        }

        return $this->cachedPanel;
    }
}
