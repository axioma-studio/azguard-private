<?php

namespace AzGuard\Commands;

use Illuminate\Console\Command;

class CreateRoleCommand extends Command
{
    protected $signature = 'az-guard:role {name} {--level=0}';

    public function handle()
    {
        $model = config('az-guard.models.role');
        $model::firstOrCreate(['name' => $this->argument('name')], ['level' => $this->option('level')]);
        $this->info('Role created.');
    }
}
