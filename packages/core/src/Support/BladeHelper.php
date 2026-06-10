<?php

declare(strict_types=1);

namespace AzGuard\Support;

final class BladeHelper
{
    public static function authed(): bool
    {
        return auth()->check();
    }
}
