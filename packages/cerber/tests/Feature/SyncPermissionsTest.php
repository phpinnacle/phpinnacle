<?php

use PHPinnacle\Cerber\Cerberus;
use PHPinnacle\Cerber\Models\Role;
use PHPinnacle\Cerber\Models\Tenant;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    $this->artisan('migrate')->assertSuccessful();
});

afterEach(function () {
    Cerberus::$permissions = [];
});

it('syncs custom guards to system roles', function () {
    Cerberus::guard('settings', 'general', [
        'manage_panel_settings' => 'Panel',
    ]);

    $role = Role::system(Tenant::default('default'), 'Super Admin');

    $this->artisan('auth:sync-permissions')->assertSuccessful();

    expect($role->refresh()->able('manage_panel_settings'))->toBeTrue();
});
