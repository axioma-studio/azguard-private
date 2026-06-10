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
use Override;
use ReflectionClass;

abstract class PanelProvider extends ServiceProvider
{
    private ?Panel $cachedPanel = null;

    abstract public function panel(Panel $panel): Panel;

    #[Override]
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
     * Registers EnumPermissionCatalogBuilder and PolicyAbilityCatalogBuilder
     * for this panel under the azguard.catalog_builders tag so that
     * CompositePermissionCatalog can assemble the full permission catalog.
     *
     * Override in a concrete PanelProvider to add custom builders or
     * replace the default ones.
     *
     * @param  list<string>  $policyClasses
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
            $abstract = 'azguard.catalog_builder.'.$panelId.'.enum';
            $this->app->instance($abstract, new EnumPermissionCatalogBuilder(
                panelId: $panelId,
                enumClasses: $permissionEnums,
            ));
            $this->app->tag([$abstract], 'azguard.catalog_builders');
        }

        if ($policyClasses !== []) {
            $abstract = 'azguard.catalog_builder.'.$panelId.'.policy';
            $this->app->instance($abstract, new PolicyAbilityCatalogBuilder(
                panelId: $panelId,
                policyClasses: $policyClasses,
            ));
            $this->app->tag([$abstract], 'azguard.catalog_builders');
        }
    }

    protected function getPanel(): Panel
    {
        if (! $this->cachedPanel instanceof Panel) {
            $reflection = new ReflectionClass($this);
            $this->cachedPanel = $this->panel(panel: Panel::make());
            $this->cachedPanel->setNamespace(namespace: $reflection->getNamespaceName());
        }

        return $this->cachedPanel;
    }
}
