<?php

declare(strict_types=1);

namespace AzGuard\Commands;

use AzGuard\Guard\GuardDoctor;
use Illuminate\Console\Command;

final class DoctorCommand extends Command
{
    protected $signature = 'guard:doctor {--panel=}';

    protected $description = 'Проверяет согласованность enum, политик и ролей AzGuard';

    protected $aliases = ['azguard:doctor'];

    public function handle(GuardDoctor $doctor): int
    {
        $panel = $this->option(key: 'panel');

        if (is_string($panel) && $panel === '') {
            $panel = null;
        }

        $result = $doctor->diagnose(panelFilter: is_string($panel) ? $panel : null);

        if ($result['abilities'] !== []) {
            $this->table(
                headers: ['Панель', 'Ability', 'Policy::method'],
                rows: array_map(
                    callback: static fn (array $row): array => [$row['panel'], $row['ability'], $row['handler']],
                    array: $result['abilities'],
                ),
            );
        }

        foreach ($result['warnings'] as $warning) {
            $this->warn($warning);
        }

        foreach ($result['errors'] as $error) {
            $this->error($error);
        }

        if ($result['errors'] !== []) {
            $this->error('guard:doctor: найдены ошибки согласованности.');

            return self::FAILURE;
        }

        $this->info('guard:doctor: проверки пройдены.');

        return self::SUCCESS;
    }
}
