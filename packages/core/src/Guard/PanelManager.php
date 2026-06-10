<?php

declare(strict_types=1);

namespace AzGuard\Guard;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Collection;

class PanelManager
{
    protected Collection $panels;

    public function __construct(protected Authorizer $authorizer)
    {
        $this->panels = collect();
    }

    public function authorize(Authorizable $user, string $ability): ?bool
    {
        return $this->authorizer->check($user, $ability);
    }
}
