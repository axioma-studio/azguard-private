<?php

declare(strict_types=1);

namespace Tests\Unit;

use AzGuard\Facades\AzGuard;
use AzGuard\Support\Panel;
use Tests\TestCase;

class BladeDirectiveTest extends TestCase
{
    // ─── @azdirect ────────────────────────────────────────────────────────────

    public function test_azdirect_renders_content_when_grant_exists(): void
    {
        $user = $this->createUserWithDirectGrant('app.documents.export', 'app');
        $this->actingAs($user);

        $panel = $this->createMock(Panel::class);
        $panel->method('getId')->willReturn('app');
        AzGuard::setCurrentPanel($panel);

        $html = $this->blade(
            '@azdirect(\'app.documents.export\') <span>OK</span> @endazdirect'
        );

        $this->assertStringContainsString('<span>OK</span>', $html);
    }

    public function test_azdirect_hides_content_when_grant_missing(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $panel = $this->createMock(Panel::class);
        $panel->method('getId')->willReturn('app');
        AzGuard::setCurrentPanel($panel);

        $html = $this->blade(
            '@azdirect(\'app.documents.export\') <span>SECRET</span> @endazdirect'
        );

        $this->assertStringNotContainsString('<span>SECRET</span>', $html);
    }

    public function test_azdirect_hidden_when_not_authenticated(): void
    {
        $html = $this->blade(
            '@azdirect(\'app.x\') <span>HIDDEN</span> @endazdirect'
        );

        $this->assertStringNotContainsString('<span>HIDDEN</span>', $html);
    }
}
