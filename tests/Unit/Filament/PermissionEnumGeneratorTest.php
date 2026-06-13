<?php

declare(strict_types=1);

use AzGuard\Filament\Permissions\PermissionEnumGenerator;
use AzGuard\Filament\Permissions\PermissionSubject;

it('derives the enum class name from the subject', function (): void {
    $generator = new PermissionEnumGenerator;

    expect($generator->className(new PermissionSubject('BlogPost', 'Blog Posts', [])))
        ->toBe('BlogPostPermission');
});

it('generates a backed permission enum with one case per ability', function (): void {
    $generator = new PermissionEnumGenerator;
    $subject = new PermissionSubject('Post', 'Posts', ['view_any', 'create'], stdClass::class);

    $source = $generator->source($subject, 'App\\Guards\\Admin\\Permissions');

    expect($source)
        ->toContain('declare(strict_types=1);')
        ->toContain('namespace App\\Guards\\Admin\\Permissions;')
        ->toContain('enum PostPermission: string')
        ->toContain("case ViewAny = 'post.view_any';")
        ->toContain("case Create = 'post.create';");
});
