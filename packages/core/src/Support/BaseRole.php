<?php

namespace AzGuard\Support;

abstract class BaseRole
{
    abstract public function permissions(): array;
}
