<?php

namespace AzGuard\Guard;

use Illuminate\Support\Collection;

class PanelManager
{
    protected Collection $panels;

    public function __construct(protected Authorizer $authorizer)
    {
        $this->panels = collect();
    }

    public function authorize($user, $ability)
    {
        return $this->authorizer->check($user, $ability);
    }
}
