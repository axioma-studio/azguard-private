<?php

declare(strict_types=1);

namespace AzGuard\Tests\Contracts;

use AzGuard\Contracts\PermissionResolverInterface;
use AzGuard\Registry\Resolver\EffectivePermissionResolver;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class PermissionResolverInterfaceTest extends TestCase
{
    public function test_effective_permission_resolver_implements_contract(): void
    {
        $reflection = new ReflectionClass(EffectivePermissionResolver::class);

        $this->assertTrue(
            $reflection->implementsInterface(PermissionResolverInterface::class),
            'EffectivePermissionResolver must implement PermissionResolverInterface'
        );
    }

    public function test_interface_declares_for_user_method(): void
    {
        $reflection = new ReflectionClass(PermissionResolverInterface::class);

        $this->assertTrue($reflection->hasMethod('forUser'));

        $method = $reflection->getMethod('forUser');
        $params = $method->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('user', $params[0]->getName());
        $this->assertSame('panelId', $params[1]->getName());
    }

    public function test_interface_declares_forget_for_user_method(): void
    {
        $reflection = new ReflectionClass(PermissionResolverInterface::class);

        $this->assertTrue($reflection->hasMethod('forgetForUser'));

        $method = $reflection->getMethod('forgetForUser');
        $returnType = (string) $method->getReturnType();

        $this->assertSame('void', $returnType);
    }

    public function test_for_user_return_type_is_permission_set(): void
    {
        $reflection = new ReflectionClass(PermissionResolverInterface::class);
        $method = $reflection->getMethod('forUser');
        $returnType = (string) $method->getReturnType();

        $this->assertStringContainsString('PermissionSet', $returnType);
    }
}
