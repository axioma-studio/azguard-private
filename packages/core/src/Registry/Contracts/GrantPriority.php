<?php

declare(strict_types=1);

namespace AzGuard\Registry\Contracts;

enum GrantPriority: int
{
    case ClassRole = 100;
    case ContextualRole = 95;
    case DatabaseRole = 90;
    case DirectGrant = 80;
}
