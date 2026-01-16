<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Feature tests for the Permit package.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

use App\Models\User;
use Permit\Models\Permission;
use Permit\Models\Role;
use Permit\Permit;
use Permit\Services\GateManagerService;
use Permit\Services\RoleManagerService;
use Testing\Support\DatabaseTestHelper;

beforeEach(function () {
    DatabaseTestHelper::setupTestEnvironment(['Permit'], true);
    resolve(GateManagerService::class)->clear();
});

afterEach(function () {
    DatabaseTestHelper::resetDefaultConnection();
});

describe('Role Management', function () {
    test('can create a role', function () {
        $role = Permit::role()
            ->slug('admin')
            ->name('Administrator')
            ->description('Full admin access')
            ->create();

        expect($role)->toBeInstanceOf(Role::class)
            ->and($role->slug)->toBe('admin')
            ->and($role->name)->toBe('Administrator')
            ->and($role->description)->toBe('Full admin access');
    });

    test('can create role with permissions', function () {
        $role = Permit::role()
            ->slug('editor')
            ->name('Editor')
            ->permissions(['posts.create', 'posts.update', 'posts.delete'])
            ->create();

        $permissions = $role->permissions()->get();

        expect($permissions)->toHaveCount(3);
    });

    test('can create hierarchical roles', function () {
        $userRole = Permit::role()
            ->slug('user')
            ->name('User')
            ->permissions(['profile.view', 'profile.update'])
            ->create();

        $adminRole = Permit::role()
            ->slug('admin')
            ->name('Admin')
            ->inherits('user')
            ->permissions(['users.manage'])
            ->create();

        expect($adminRole->parent_id)->toBe($userRole->id);

        // Admin should inherit user permissions
        $allPermissions = $adminRole->allPermissions();
        expect($allPermissions)->toHaveCount(3);
    });

    test('can find role by slug', function () {
        Permit::role()->slug('moderator')->name('Moderator')->create();

        $role = resolve(RoleManagerService::class)->find('moderator');

        expect($role)->not->toBeNull()
            ->and($role->name)->toBe('Moderator');
    });

    test('can delete a role', function () {
        $role = Permit::role()->slug('temp')->name('Temp')->create();
        $roleId = $role->id;

        resolve(RoleManagerService::class)->delete('temp');

        expect(Role::find($roleId))->toBeNull();
    });
});

describe('Permission Management', function () {
    test('can create a permission', function () {
        $permission = Permit::permission()
            ->slug('posts.create')
            ->name('Create Posts')
            ->description('Allows creating new posts')
            ->group('posts')
            ->create();

        expect($permission)->toBeInstanceOf(Permission::class)
            ->and($permission->slug)->toBe('posts.create')
            ->and($permission->group)->toBe('posts');
    });

    test('can find or create permission', function () {
        $permission1 = Permission::findOrCreate('users.view');
        $permission2 = Permission::findOrCreate('users.view');

        expect($permission1->id)->toBe($permission2->id);
    });

    test('can group permissions', function () {
        Permit::permission()->slug('posts.create')->group('posts')->create();
        Permit::permission()->slug('posts.update')->group('posts')->create();
        Permit::permission()->slug('users.view')->group('users')->create();

        $grouped = Permission::grouped();

        expect($grouped)->toHaveKey('posts')
            ->and($grouped['posts'])->toHaveCount(2)
            ->and($grouped)->toHaveKey('users')
            ->and($grouped['users'])->toHaveCount(1);
    });
});

describe('Role-Permission Assignment', function () {
    test('can assign permission to role', function () {
        $role = Permit::role()->slug('writer')->name('Writer')->create();
        $permission = Permission::findOrCreate('posts.write');

        $role->givePermission($permission);

        expect($role->hasPermission('posts.write'))->toBeTrue();
    });

    test('can revoke permission from role', function () {
        $role = Permit::role()
            ->slug('editor')
            ->permissions(['posts.edit', 'posts.delete'])
            ->create();

        expect($role->hasPermission('posts.edit'))->toBeTrue();

        $role->revokePermission('posts.edit');

        expect($role->hasPermission('posts.edit'))->toBeFalse()
            ->and($role->hasPermission('posts.delete'))->toBeTrue();
    });

    test('can sync permissions for role', function () {
        $role = Permit::role()
            ->slug('manager')
            ->permissions(['perm1', 'perm2', 'perm3'])
            ->create();

        expect($role->permissions()->get())->toHaveCount(3);

        Permission::findOrCreate('perm4');
        Permission::findOrCreate('perm5');

        $role->syncPermissions([
            Permission::findBySlug('perm4'),
            Permission::findBySlug('perm5'),
        ]);

        $permissions = $role->permissions()->get();
        expect($permissions)->toHaveCount(2);

        $slugs = $permissions->pluck('slug');
        expect($slugs)->toContain('perm4')
            ->and($slugs)->toContain('perm5');
    });
});

describe('Hierarchical Permission Inheritance', function () {
    test('child roles inherit parent permissions', function () {
        $baseRole = Permit::role()
            ->slug('base')
            ->permissions(['base.perm1', 'base.perm2'])
            ->create();

        $childRole = Permit::role()
            ->slug('child')
            ->inherits('base')
            ->permissions(['child.perm1'])
            ->create();

        expect($childRole->hasPermission('child.perm1'))->toBeTrue()
            ->and($childRole->hasPermission('base.perm1'))->toBeTrue()
            ->and($childRole->hasPermission('base.perm2'))->toBeTrue();
    });

    test('multi-level inheritance works', function () {
        $level1 = Permit::role()->slug('level1')->permissions(['perm1'])->create();
        $level2 = Permit::role()->slug('level2')->inherits('level1')->permissions(['perm2'])->create();
        $level3 = Permit::role()->slug('level3')->inherits('level2')->permissions(['perm3'])->create();

        $allPermissions = $level3->allPermissions();

        expect($allPermissions)->toHaveCount(3);
    });

    test('can get ancestors and descendants', function () {
        $grandparent = Permit::role()->slug('grandparent')->create();
        $parent = Permit::role()->slug('parent')->inherits('grandparent')->create();
        $child = Permit::role()->slug('child')->inherits('parent')->create();

        $ancestors = $child->ancestors();
        expect($ancestors)->toHaveCount(2);

        $descendants = $grandparent->descendants();
        expect($descendants)->toHaveCount(2);
    });
});

describe('Gate Definitions', function () {
    test('can define and check gates', function () {
        $manager = resolve(GateManagerService::class);
        $manager->define('view-dashboard', function ($user) {
            return true;
        });

        $result = $manager->check('view-dashboard', DatabaseTestHelper::createMockUser());

        expect($result)->toBeTrue();
    });

    test('undefined gates return null', function () {
        $result = resolve(GateManagerService::class)->check('undefined-gate', DatabaseTestHelper::createMockUser());

        expect($result)->toBeNull();
    });

    test('before callbacks can override gate checks', function () {
        $manager = resolve(GateManagerService::class);
        $manager->clear(); // Clear previously registered gates/callbacks
        $manager->before(function ($user, $ability) {
            if ($ability === 'admin-only') {
                return false;
            }

            return null;
        });

        $manager->define('admin-only', fn () => true);

        $result = $manager->check('admin-only', DatabaseTestHelper::createMockUser());

        expect($result)->toBeFalse();
    });
});

describe('Permit Sync', function () {
    test('can sync roles with permissions from array', function () {
        Permit::sync([
            'admin' => [
                'name' => 'Administrator',
                'permissions' => ['users.view', 'users.create', 'users.delete'],
            ],
            'user' => [
                'name' => 'User',
                'permissions' => ['profile.view'],
            ],
        ]);

        $admin = Role::findBySlug('admin');
        $user = Role::findBySlug('user');

        expect($admin)->not->toBeNull()
            ->and($admin->permissions()->get())->toHaveCount(3)
            ->and($user)->not->toBeNull()
            ->and($user->permissions()->get())->toHaveCount(1);

        $adminSlugs = $admin->permissions()->get()->pluck('slug');
        expect($adminSlugs)->toContain('users.view')
            ->and($adminSlugs)->toContain('users.create')
            ->and($adminSlugs)->toContain('users.delete');
    });
});
