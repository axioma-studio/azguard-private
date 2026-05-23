<?php

declare(strict_types=1);

namespace AzGuard\Tests\Stubs;

use AzGuard\PanelProvider;
use AzGuard\Support\Panel;

final class TestGuardPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id(id: 'test')
            ->path(path: 'test');
    }
}
