<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use AzGuard\Auth\DirectGrantPolicy;
use AzGuard\Facades\AzGuard;
use AzGuard\Support\Panel;
use Tests\TestCase;

class DirectGrantPolicyTest extends TestCase
{
    private DirectGrantPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new DirectGrantPolicy;
    }

    public function test_returns_false_if_model_lacks_hasDirectGrant(): void
    {
        $user = $this->createUserWithoutGrantsTrait();

        $result = $this->policy->check($user, 'app.x');

        $this->assertFalse($result);
    }

    public function test_delegates_to_hasDirectGrant_with_string_arg(): void
    {
        $user = $this->createUser();
        $user->expects($this->once())
            ->method('hasDirectGrant')
            ->with('app.x', null)
            ->willReturn(true);

        $result = $this->policy->check($user, 'app.x');

        $this->assertTrue($result);
    }

    public function test_delegates_with_array_arg(): void
    {
        $user = $this->createUser();
        $user->expects($this->once())
            ->method('hasDirectGrant')
            ->with('app.x', 'app')
            ->willReturn(false);

        $result = $this->policy->check($user, ['app.x', 'app']);

        $this->assertFalse($result);
    }

    public function test_uses_current_panel_when_panel_not_set(): void
    {
        $panel = $this->createMock(Panel::class);
        $panel->method('getId')->willReturn('app');
        AzGuard::setCurrentPanel($panel);

        $user = $this->createUser();
        $user->expects($this->once())
            ->method('hasDirectGrant')
            ->with('app.x', 'app')
            ->willReturn(true);

        $result = $this->policy->check($user, ['app.x']);

        $this->assertTrue($result);
    }

    public function test_gate_integration(): void
    {
        $user = $this->createUserWithDirectGrant('app.documents.export', 'app');
        $this->actingAs($user);

        $this->assertTrue(\Illuminate\Support\Facades\Gate::allows('direct-grant', 'app.documents.export'));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::allows('direct-grant', 'app.documents.delete'));
    }
}
