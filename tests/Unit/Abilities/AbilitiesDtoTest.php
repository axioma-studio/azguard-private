<?php

declare(strict_types=1);

use AzGuard\Abilities\AbilitiesDto;
use Closure;
use Illuminate\Support\Facades\Gate;

/**
 * Concrete subclass fixture mirroring the generated domain-abilities.stub shape:
 * a promoted bool constructor plus an abilityMap() keyed by those bool fields.
 */
final readonly class PostAbilitiesFixture extends AbilitiesDto
{
    public function __construct(
        public bool $viewAny,
        public bool $view,
        public bool $create,
        public bool $update,
        public bool $delete,
    ) {}

    /**
     * @return array<string, string>
     */
    protected static function abilityMap(): array
    {
        return [
            'viewAny' => 'fixture.posts.viewAny',
            'view' => 'fixture.posts.view',
            'create' => 'fixture.posts.create',
            'update' => 'fixture.posts.update',
            'delete' => 'fixture.posts.delete',
        ];
    }
}

/**
 * Define every fixture ability with the same guest-evaluable resolver.
 * A nullable user param makes Gate run the callback for a guest instead of
 * short-circuiting to false.
 */
function defineFixtureAbilities(Closure $resolver): void
{
    foreach (['viewAny', 'view', 'create', 'update', 'delete'] as $ability) {
        Gate::define("fixture.posts.{$ability}", fn (?object $user): bool => $resolver());
    }
}

describe('AbilitiesDto::make()', function () {
    it('instantiates the concrete subclass through make()', function () {
        defineFixtureAbilities(fn (): bool => true);

        $abilities = PostAbilitiesFixture::make();

        expect($abilities)
            ->toBeInstanceOf(PostAbilitiesFixture::class)
            ->toBeInstanceOf(AbilitiesDto::class);
    });

    it('resolves every bool flag as true when Gate allows all abilities', function () {
        defineFixtureAbilities(fn (): bool => true);

        $abilities = PostAbilitiesFixture::make();

        expect($abilities->viewAny)->toBeTrue()
            ->and($abilities->view)->toBeTrue()
            ->and($abilities->create)->toBeTrue()
            ->and($abilities->update)->toBeTrue()
            ->and($abilities->delete)->toBeTrue();
    });

    it('resolves every bool flag as false when Gate denies all abilities', function () {
        defineFixtureAbilities(fn (): bool => false);

        $abilities = PostAbilitiesFixture::make();

        expect($abilities->viewAny)->toBeFalse()
            ->and($abilities->view)->toBeFalse()
            ->and($abilities->create)->toBeFalse()
            ->and($abilities->update)->toBeFalse()
            ->and($abilities->delete)->toBeFalse();
    });

    it('maps each Gate result onto the correctly named constructor argument', function () {
        // Nullable user so Gate evaluates the callback for a guest instead of
        // short-circuiting to false.
        Gate::define('fixture.posts.viewAny', fn (?object $user): bool => true);
        Gate::define('fixture.posts.view', fn (?object $user): bool => true);
        Gate::define('fixture.posts.create', fn (?object $user): bool => false);
        Gate::define('fixture.posts.update', fn (?object $user): bool => true);
        Gate::define('fixture.posts.delete', fn (?object $user): bool => false);

        $abilities = PostAbilitiesFixture::make();

        // Named-argument unpacking must land each flag on its own field,
        // not by positional order.
        expect($abilities->viewAny)->toBeTrue()
            ->and($abilities->view)->toBeTrue()
            ->and($abilities->create)->toBeFalse()
            ->and($abilities->update)->toBeTrue()
            ->and($abilities->delete)->toBeFalse();
    });

    it('exposes resolved flags via toArray()', function () {
        Gate::define('fixture.posts.viewAny', fn (?object $user): bool => true);
        Gate::define('fixture.posts.view', fn (?object $user): bool => false);
        Gate::define('fixture.posts.create', fn (?object $user): bool => false);
        Gate::define('fixture.posts.update', fn (?object $user): bool => false);
        Gate::define('fixture.posts.delete', fn (?object $user): bool => true);

        $abilities = PostAbilitiesFixture::make();

        expect($abilities->toArray())->toBe([
            'viewAny' => true,
            'view' => false,
            'create' => false,
            'update' => false,
            'delete' => true,
        ]);
    });

    it('forwards make() arguments to Gate as authorization arguments', function () {
        $received = null;

        Gate::define('fixture.posts.viewAny', function (?object $user, $model) use (&$received): bool {
            $received = $model;

            return true;
        });
        Gate::define('fixture.posts.view', fn (?object $user): bool => true);
        Gate::define('fixture.posts.create', fn (?object $user): bool => true);
        Gate::define('fixture.posts.update', fn (?object $user): bool => true);
        Gate::define('fixture.posts.delete', fn (?object $user): bool => true);

        $subject = new stdClass;

        PostAbilitiesFixture::make($subject);

        expect($received)->toBe($subject);
    });
});
