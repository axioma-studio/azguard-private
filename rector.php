<?php

use Rector\Config\RectorConfig;
use Rector\Laravel\Set\LaravelSetList;

return RectorConfig::configure()->withPaths([__DIR__.'/packages'])->withSets([LaravelSetList::LARAVEL_100]);
