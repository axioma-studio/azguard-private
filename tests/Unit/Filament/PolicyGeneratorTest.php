<?php

declare(strict_types=1);

use AzGuard\Filament\Permissions\PermissionSchema;
use AzGuard\Filament\Permissions\PermissionSubject;
use AzGuard\Filament\Permissions\PolicyGenerator;

it('derives the policy class name from the model', function (): void {
    $generator = new PolicyGenerator;

    expect($generator->className(new PermissionSubject('Post', 'Posts', [], 'App\\Models\\Post')))
        ->toBe('PostPolicy');
});

it('generates a policy whose methods check the matching permission', function (): void {
    $generator = new PolicyGenerator;
    $subject = new PermissionSubject('Post', 'Posts', ['view_any', 'view', 'delete'], 'App\\Models\\Post');

    $source = $generator->source($subject, 'admin', new PermissionSchema, 'App\\Policies', 'App\\Models\\User');

    expect($source)
        ->toContain('namespace App\\Policies;')
        ->toContain('use App\\Models\\Post;')
        ->toContain('use App\\Models\\User;')
        ->toContain('class PostPolicy')
        // collection-level method: user only
        ->toContain('public function viewAny(User $user): bool')
        ->toContain("hasPermission('admin.post.view_any', 'admin')")
        // record-level method: user + record
        ->toContain('public function view(User $user, Post $record): bool')
        ->toContain('public function delete(User $user, Post $record): bool')
        // bulk variant shares the singular permission, collection-level signature
        ->toContain('public function deleteAny(User $user): bool')
        ->toContain("hasPermission('admin.post.delete', 'admin')");
});

it('only generates methods for the configured abilities', function (): void {
    $generator = new PolicyGenerator;
    $subject = new PermissionSubject('Tag', 'Tags', ['view_any'], 'App\\Models\\Tag');

    $source = $generator->source($subject, 'admin', new PermissionSchema, 'App\\Policies', 'App\\Models\\User');

    expect($source)
        ->toContain('public function viewAny(User $user): bool')
        ->not->toContain('public function create')
        ->not->toContain('public function delete');
});
