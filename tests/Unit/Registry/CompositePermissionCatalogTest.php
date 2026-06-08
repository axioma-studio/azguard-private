<?php

declare(strict_types=1);

use AzGuard\Registry\Builders\CompositePermissionCatalog;
use AzGuard\Registry\Contracts\PermissionCatalogBuilder;
use AzGuard\Registry\Contracts\PermissionDefinition;
use AzGuard\Registry\Exceptions\InvalidCatalogException;
use AzGuard\Registry\Exceptions\InvalidPermissionKeyException;

// ─── Helpers ──────────────────────────────────────────────────────────────

/**
 * Быстрая заглушка PermissionDefinition.
 */
function makeDefinition(string $key, string $group = 'General'): PermissionDefinition
{
    return new class ($key, $group) implements PermissionDefinition {
        public function __construct(private string $k, private string $g) {}
        public function key(): string   { return $this->k; }
        public function label(): string { return $this->k; }
        public function group(): ?string { return $this->g; }
        public function meta(): array   { return []; }
    };
}

/**
 * Заглушка PermissionCatalogBuilder, возвращающая фиксированный набор.
 *
 * @param list<PermissionDefinition> $definitions
 */
function makeBuilder(array $definitions, bool $supports = true): PermissionCatalogBuilder
{
    return new class ($definitions, $supports) implements PermissionCatalogBuilder {
        public function __construct(
            private array $defs,
            private bool $sup,
        ) {}

        public function build(string $panelId): array { return $this->defs; }
        public function supports(string $panelId): bool { return $this->sup; }
    };
}

// ─── Tests ────────────────────────────────────────────────────────────────

describe('CompositePermissionCatalog', function () {

    it('returns empty list for panel with no builders', function () {
        $catalog = new CompositePermissionCatalog(
            builders: [],
            panelIds: ['app'],
        );

        expect($catalog->all('app'))->toBe([]);
    });

    it('aggregates definitions from multiple builders', function () {
        $defA = makeDefinition('app.posts.view');
        $defB = makeDefinition('app.posts.edit');

        $catalog = new CompositePermissionCatalog(
            builders: [makeBuilder([$defA]), makeBuilder([$defB])],
            panelIds: ['app'],
        );

        $all = $catalog->all('app');

        expect($all)->toHaveCount(2);
        expect(array_map(fn ($d) => $d->key(), $all))
            ->toContain('app.posts.view')
            ->toContain('app.posts.edit');
    });

    it('deduplicates same key from two builders when groups match', function () {
        $def1 = makeDefinition('app.posts.view', 'Posts');
        $def2 = makeDefinition('app.posts.view', 'Posts');

        $catalog = new CompositePermissionCatalog(
            builders: [makeBuilder([$def1]), makeBuilder([$def2])],
            panelIds: ['app'],
        );

        expect($catalog->all('app'))->toHaveCount(1);
    });

    it('throws InvalidCatalogException on conflicting groups for same key', function () {
        $def1 = makeDefinition('app.posts.view', 'Posts');
        $def2 = makeDefinition('app.posts.view', 'Articles'); // разная группа!

        $catalog = new CompositePermissionCatalog(
            builders: [makeBuilder([$def1]), makeBuilder([$def2])],
            panelIds: ['app'],
        );

        expect(fn () => $catalog->all('app'))
            ->toThrow(InvalidCatalogException::class);
    });

    it('has() returns true for registered key', function () {
        $catalog = new CompositePermissionCatalog(
            builders: [makeBuilder([makeDefinition('app.posts.view')])],
            panelIds: ['app'],
        );

        expect($catalog->has('app', 'app.posts.view'))->toBeTrue()
            ->and($catalog->has('app', 'app.posts.delete'))->toBeFalse();
    });

    it('get() returns definition or null', function () {
        $def = makeDefinition('app.posts.view');
        $catalog = new CompositePermissionCatalog(
            builders: [makeBuilder([$def])],
            panelIds: ['app'],
        );

        expect($catalog->get('app', 'app.posts.view'))->toBe($def)
            ->and($catalog->get('app', 'missing'))->toBeNull();
    });

    it('assert() throws InvalidPermissionKeyException for unknown key', function () {
        $catalog = new CompositePermissionCatalog(
            builders: [makeBuilder([makeDefinition('app.posts.view')])],
            panelIds: ['app'],
        );

        expect(fn () => $catalog->assert('app', 'app.missing'))
            ->toThrow(InvalidPermissionKeyException::class);
    });

    it('groups() returns definitions sorted by group name', function () {
        $catalog = new CompositePermissionCatalog(
            builders: [makeBuilder([
                makeDefinition('app.posts.view', 'Posts'),
                makeDefinition('app.tags.view', 'Tags'),
                makeDefinition('app.posts.edit', 'Posts'),
            ])],
            panelIds: ['app'],
        );

        $groups = $catalog->groups('app');

        expect(array_keys($groups))->toBe(['Posts', 'Tags'])
            ->and($groups['Posts'])->toHaveCount(2)
            ->and($groups['Tags'])->toHaveCount(1);
    });

    it('groups() uses Other for null group', function () {
        $def = new class implements PermissionDefinition {
            public function key(): string    { return 'app.x'; }
            public function label(): string  { return 'x'; }
            public function group(): ?string { return null; }
            public function meta(): array    { return []; }
        };

        $catalog = new CompositePermissionCatalog(
            builders: [makeBuilder([$def])],
            panelIds: ['app'],
        );

        expect(array_keys($catalog->groups('app')))->toBe(['Other']);
    });

    it('panels() returns registered panel IDs', function () {
        $catalog = new CompositePermissionCatalog(
            builders: [],
            panelIds: ['app', 'admin'],
        );

        expect($catalog->panels())->toBe(['app', 'admin']);
    });

    it('flush() resets built state and allows re-build', function () {
        $catalog = new CompositePermissionCatalog(
            builders: [makeBuilder([makeDefinition('app.posts.view')])],
            panelIds: ['app'],
        );

        // Первая сборка
        expect($catalog->has('app', 'app.posts.view'))->toBeTrue();

        // Flush — сброс
        $catalog->flush();

        // После flush — пустой каталог (definitions = [])
        expect($catalog->get('app', 'app.posts.view'))->toBeNull();
    });

    it('skips builder that does not support panel', function () {
        $supported   = makeBuilder([makeDefinition('app.posts.view')], supports: true);
        $unsupported = makeBuilder([makeDefinition('app.posts.edit')], supports: false);

        $catalog = new CompositePermissionCatalog(
            builders: [$supported, $unsupported],
            panelIds: ['app'],
        );

        expect($catalog->all('app'))->toHaveCount(1)
            ->and($catalog->has('app', 'app.posts.edit'))->toBeFalse();
    });
});
