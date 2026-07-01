<?php

declare(strict_types=1);

/**
 * Enforces the @api/@internal SemVer boundary (F10) at the source level, since
 * PHPStan's native @internal check only fires across composer packages and would
 * miss an internal type leaking into a public signature within core.
 *
 *  1. Every published contract (Contracts/, Registry/Contracts/) must carry @api.
 *  2. No @api type may reference an @internal type in a public method signature.
 */
$coreRoot = dirname(__DIR__, 2).'/packages/core/src';

/** @return list<class-string> */
$classesIn = function (string $subdir) use ($coreRoot): array {
    $dir = "$coreRoot/$subdir";
    $out = [];

    if (! is_dir($dir)) {
        return $out;
    }

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $relative = substr($file->getPathname(), strlen($coreRoot) + 1, -4);
        /** @var class-string $fqcn */
        $fqcn = 'AzGuard\\'.str_replace('/', '\\', $relative);
        $out[] = $fqcn;
    }

    return $out;
};

$hasTag = function (string $fqcn, string $tag): bool {
    $doc = (new ReflectionClass($fqcn))->getDocComment();

    // Matches both multi-line (" * @api") and single-line ("/** @api */") docblocks.
    return is_string($doc) && preg_match('/'.preg_quote($tag, '/').'\b/', $doc) === 1;
};

test('every published contract carries @api', function () use ($classesIn, $hasTag) {
    $contracts = [...$classesIn('Contracts'), ...$classesIn('Registry/Contracts')];

    expect($contracts)->not->toBeEmpty();

    foreach ($contracts as $fqcn) {
        expect(class_exists($fqcn) || interface_exists($fqcn) || enum_exists($fqcn))->toBeTrue();
        expect($hasTag($fqcn, '@api'))->toBeTrue("Published contract [{$fqcn}] must declare @api.");
    }
});

test('no @api type references an @internal type in a public signature', function () use ($classesIn, $hasTag) {
    // Collect every @internal AzGuard type across core.
    $allCore = $classesIn('');
    $internal = [];

    foreach ($allCore as $fqcn) {
        if ((class_exists($fqcn) || interface_exists($fqcn) || enum_exists($fqcn)) && $hasTag($fqcn, '@internal')) {
            $internal[$fqcn] = true;
        }
    }

    expect($internal)->not->toBeEmpty();

    $referencedInternal = static function (?ReflectionType $type) use ($internal): array {
        if ($type === null) {
            return [];
        }

        $named = $type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType
            ? $type->getTypes()
            : [$type];

        $leaked = [];

        foreach ($named as $t) {
            if ($t instanceof ReflectionNamedType && ! $t->isBuiltin() && isset($internal[$t->getName()])) {
                $leaked[] = $t->getName();
            }
        }

        return $leaked;
    };

    foreach ($allCore as $fqcn) {
        if (! (class_exists($fqcn) || interface_exists($fqcn) || enum_exists($fqcn)) || ! $hasTag($fqcn, '@api')) {
            continue;
        }

        $reflection = new ReflectionClass($fqcn);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $fqcn) {
                continue;
            }

            $leaks = $referencedInternal($method->getReturnType());

            foreach ($method->getParameters() as $param) {
                $leaks = [...$leaks, ...$referencedInternal($param->getType())];
            }

            expect($leaks)->toBe([], "@api {$fqcn}::{$method->getName()}() leaks @internal type(s): ".implode(', ', $leaks));
        }
    }
});
