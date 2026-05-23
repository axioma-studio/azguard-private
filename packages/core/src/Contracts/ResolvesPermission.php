<?php

declare(strict_types=1);

namespace AzGuard\Contracts;

use AzGuard\Support\Panel;

interface ResolvesPermission
{
    public function resolve(Panel $panel): string;
}
