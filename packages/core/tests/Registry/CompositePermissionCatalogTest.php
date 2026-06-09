<?php

declare(strict_types=1);

namespace AzGuard\Tests\Registry;

use AzGuard\Registry\Builders\CompositePermissionCatalog;
use AzGuard\Registry\Contracts\PermissionCatalogBuilder;
use AzGuard\Registry\Definitions\EnumPermissionDefinition;
use AzGuard\Registry\Exceptions\InvalidPermissionKeyException;
use PHPUnit\Framework\TestCase;

enum CatalogTestPermission: string
{
    case View = 'view';
    case Create = 'create';
}

final class CompositePermissionCatalogTest extends TestCase
{
    private function makeBuilder(string $panelId, array $definitions): PermissionCatalogBuilder
    {
        return new class($panelId, $definitions) implements PermissionCatalogBuilder
        {
            public function __construct(
                private readonly string $panel,
                private readonly array $defs,
            ) {}

            public function build(string $panelId): array
            {
                return $this->defs;
            }

            public function supports(string $panelId): bool
            {
                return $panelId === $this->panel;
            }
        };
    }

    public function test_all_returns_definitions_for_panel(): void
    {
        $def = EnumPermissionDefinition::fromCase(
            CatalogTestPermission::View, 'app', 'app.catalog.view'
        );

        $catalog = new CompositePermissionCatalog(
            builders: [$this->makeBuilder('app', [$def])],
            panelIds: ['app'],
        );

        $this->assertCount(1, $catalog->all('app'));
        $this->assertSame('app.catalog.view', $catalog->all('app')[0]->key());
    }

    public function test_has_returns_true_for_registered_key(): void
    {
        $def = EnumPermissionDefinition::fromCase(
            CatalogTestPermission::View, 'app', 'app.catalog.view'
        );

        $catalog = new CompositePermissionCatalog(
            builders: [$this->makeBuilder('app', [$def])],
            panelIds: ['app'],
        );

        $this->assertTrue($catalog->has('app', 'app.catalog.view'));
        $this->assertFalse($catalog->has('app', 'app.catalog.delete'));
    }

    public function test_assert_throws_for_unknown_key(): void
    {
        $catalog = new CompositePermissionCatalog(
            builders: [$this->makeBuilder('app', [])],
            panelIds: ['app'],
        );

        $this->expectException(InvalidPermissionKeyException::class);
        $catalog->assert('app', 'app.nonexistent.key');
    }

    public function test_groups_organizes_by_group(): void
    {
        $def1 = EnumPermissionDefinition::fromCase(CatalogTestPermission::View, 'app', 'app.catalog.view');
        $def2 = EnumPermissionDefinition::fromCase(CatalogTestPermission::Create, 'app', 'app.catalog.create');

        $catalog = new CompositePermissionCatalog(
            builders: [$this->makeBuilder('app', [$def1, $def2])],
            panelIds: ['app'],
        );

        $groups = $catalog->groups('app');

        $this->assertArrayHasKey('CatalogTest', $groups);
        $this->assertCount(2, $groups['CatalogTest']);
    }

    public function test_deduplication_of_same_key_from_two_builders(): void
    {
        $def = EnumPermissionDefinition::fromCase(
            CatalogTestPermission::View, 'app', 'app.catalog.view'
        );

        // Оба builder возвращают одинаковый key + одинаковую group
        $catalog = new CompositePermissionCatalog(
            builders: [
                $this->makeBuilder('app', [$def]),
                $this->makeBuilder('app', [$def]),
            ],
            panelIds: ['app'],
        );

        // Молчаливая дедупликация — одна запись
        $this->assertCount(1, $catalog->all('app'));
    }

    public function test_panels_returns_registered_panel_ids(): void
    {
        $catalog = new CompositePermissionCatalog(
            builders: [],
            panelIds: ['app', 'admin'],
        );

        $this->assertSame(['app', 'admin'], $catalog->panels());
    }

    public function test_flush_resets_built_state(): void
    {
        $def = EnumPermissionDefinition::fromCase(
            CatalogTestPermission::View, 'app', 'app.catalog.view'
        );

        $catalog = new CompositePermissionCatalog(
            builders: [$this->makeBuilder('app', [$def])],
            panelIds: ['app'],
        );

        $catalog->all('app'); // trigger build
        $catalog->flush();

        // После flush пересобирается заново
        $this->assertCount(1, $catalog->all('app'));
    }
}
