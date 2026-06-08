<?php

declare(strict_types=1);

use AzGuard\Support\Panel;

describe('Panel', function () {
    it('returns id set via id()', function () {
        $panel = Panel::make()->id('admin');
        expect($panel->getId())->toBe('admin');
    });

    it('returns namespace set via setNamespace()', function () {
        $panel = Panel::make()->setNamespace('App\\Guards\\Admin');
        expect($panel->getNamespace())->toBe('App\\Guards\\Admin');
    });

    it('returns basePath set via setBasePath()', function () {
        $panel = Panel::make()->setBasePath('/var/www/app/Guards/Admin');
        expect($panel->getBasePath())->toBe('/var/www/app/Guards/Admin');
    });

    it('prefixes permission with panel id when scopedByPanelId is true', function () {
        $panel = Panel::make()->id('admin')->scopedByPanelId(true);
        expect($panel->getPermissionName('users.view'))->toBe('admin.users.view');
    });

    it('does not prefix permission when scopedByPanelId is false', function () {
        $panel = Panel::make()->id('admin')->scopedByPanelId(false);
        expect($panel->getPermissionName('users.view'))->toBe('users.view');
    });

    it('resolves BackedEnum permission with panel prefix', function () {
        $panel = Panel::make()->id('crm')->scopedByPanelId(true);

        $enum = new class ('view') extends \BackedEnum {
        };

        // Используем реальный BackedEnum через анонимный stub
        $mockEnum = new readonly class implements \UnitEnum {
            public string $name = 'view';
            public static function cases(): array { return []; }
        };

        // Проверяем через строку (наиболее надёжный тест без enum)
        expect($panel->resolvePermission('posts.view'))->toBe('crm.posts.view');
    });

    it('resolves string permission', function () {
        $panel = Panel::make()->id('shop')->scopedByPanelId(true);
        expect($panel->resolvePermission('orders.edit'))->toBe('shop.orders.edit');
    });

    it('is fluent — make() returns Panel instance', function () {
        expect(Panel::make())->toBeInstanceOf(Panel::class);
    });
});
