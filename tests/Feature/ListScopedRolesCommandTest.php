<?php

declare(strict_types=1);

use AzGuard\Models\Role;
use AzGuard\Tests\Stubs\Project;
use AzGuard\Tests\Stubs\Roles\ProjectEditorRole;
use AzGuard\Tests\Stubs\User;

describe('azguard:list-scoped-roles command', function (): void {

    it('shows warning when user has no scoped roles', function (): void {
        $user = User::factory()->create();

        $this->artisan('azguard:list-scoped-roles', ['user' => $user->id])
            ->expectsOutputToContain('нет scoped-ролей')
            ->assertExitCode(0);
    });

    it('lists scoped roles for user by ID', function (): void {
        $user    = User::factory()->create();
        $project = Project::factory()->create();

        $role = Role::create([
            'name'       => 'project-editor',
            'class_name' => ProjectEditorRole::class,
            'level'      => 5,
        ]);

        $user->assignScopedRole($role, $project);

        $this->artisan('azguard:list-scoped-roles', ['user' => $user->id])
            ->expectsOutputToContain('project-editor')
            ->expectsOutputToContain('Project')
            ->assertExitCode(0);
    });

    it('lists scoped roles for user by email', function (): void {
        $user    = User::factory()->create(['email' => 'editor@example.com']);
        $project = Project::factory()->create();

        $role = Role::create([
            'name'       => 'project-editor-email',
            'class_name' => ProjectEditorRole::class,
            'level'      => 5,
        ]);

        $user->assignScopedRole($role, $project);

        $this->artisan('azguard:list-scoped-roles', ['user' => 'editor@example.com'])
            ->expectsOutputToContain('project-editor-email')
            ->assertExitCode(0);
    });

    it('returns failure for unknown user', function (): void {
        $this->artisan('azguard:list-scoped-roles', ['user' => '99999'])
            ->expectsOutputToContain('не найден')
            ->assertExitCode(1);
    });

    it('filters by entity type via --entity option', function (): void {
        $user     = User::factory()->create();
        $project  = Project::factory()->create();

        $role = Role::create([
            'name'       => 'filtered-editor',
            'class_name' => ProjectEditorRole::class,
            'level'      => 5,
        ]);

        $user->assignScopedRole($role, $project);

        // Filter matches — should show the role
        $this->artisan('azguard:list-scoped-roles', [
            'user'     => $user->id,
            '--entity' => AzGuard\Tests\Stubs\Project::class,
        ])
            ->expectsOutputToContain('filtered-editor')
            ->assertExitCode(0);

        // Filter does NOT match — should warn no scoped roles
        $this->artisan('azguard:list-scoped-roles', [
            'user'     => $user->id,
            '--entity' => 'App\\Models\\Team',
        ])
            ->expectsOutputToContain('нет scoped-ролей')
            ->assertExitCode(0);
    });
});
