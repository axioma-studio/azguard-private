<?php

declare(strict_types=1);

namespace AzGuard;

use AzGuard\Auth\PolicyAttributeRegistrar;
use AzGuard\Facades\AzGuard;
use AzGuard\Guard\PolicyDiscovery;
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
