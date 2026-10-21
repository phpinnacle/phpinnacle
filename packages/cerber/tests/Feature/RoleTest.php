<?php

use PHPinnacle\Cerber\Models\Permission;
use PHPinnacle\Cerber\Models\Role;
use PHPinnacle\Cerber\Models\Tenant;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config()->set([
        'database.connections.cerber' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'foreign_key_constraints' => true,
        ],
        'phpinnacle-cerber.connection' => 'cerber',
    ]);
    $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    $this->artisan('migrate')->assertSuccessful();
    $this->tenant = Tenant::default('default');
});

it('registers a role in the requested tenant and reuses it on subsequent calls', function () {
    $other = Tenant::locate('other');
    $defaultRole = Role::register($this->tenant, 'Editor');
    $role = Role::register($other, 'Editor', ['orders.view']);

    expect($role->tenant_id)
        ->toBe($other->id)
        ->and($role->id)
        ->not
        ->toBe($defaultRole->id)
        ->and(Role::register($other, 'Editor', ['orders.edit'])->id)
        ->toBe($role->id)
        ->and(Role::query()->count())
        ->toBe(2);
});

it('replaces loaded permissions and immediately reflects the new grants', function () {
    $role = Role::register($this->tenant, 'Editor', ['orders.view']);
    expect($role->able('orders.view'))->toBeTrue();

    $role->grant('orders.edit', 'orders.edit');

    expect($role->able('orders.view'))
        ->toBeFalse()
        ->and($role->able('orders.edit'))
        ->toBeTrue()
        ->and($role->fresh()->permissions->pluck('name')->all())
        ->toBe(['orders.edit']);

    $role->grant();

    expect($role->able('orders.edit'))->toBeFalse()->and($role->fresh()->permissions)->toBeEmpty();
});

it('rolls back permission registration on the configured package connection', function () {
    $role = Role::register($this->tenant, 'Editor', ['orders.view']);
    Permission::created(function () {
        throw new RuntimeException('Permission persistence failed.');
    });

    expect(fn () => $role->grant('orders.edit'))->toThrow(RuntimeException::class, 'Permission persistence failed.');

    expect(Permission::findByName('orders.edit'))
        ->toBeNull()
        ->and($role->fresh()->permissions->pluck('name')->all())
        ->toBe(['orders.view']);
});
