<?php

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\PanelRegistry;
use PHPinnacle\Cerber\Cerberus;
use PHPinnacle\Cerber\Enums\UserStatus;
use PHPinnacle\Cerber\Models\Login;
use PHPinnacle\Cerber\Models\Permission;
use PHPinnacle\Cerber\Models\Role;
use PHPinnacle\Cerber\Models\Tenant;
use PHPinnacle\Cerber\Models\User;
use PHPinnacle\Cerber\Resources\Users\Actions\StatusAction;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    $this->artisan('migrate')->assertSuccessful();

    $this->panel = Panel::make()->id('admin')->default();
    app(PanelRegistry::class)->register($this->panel);
    Filament::setCurrentPanel($this->panel);
    Cerberus::registerScoped();

    $this->tenant = Tenant::default('default');
    $this->user = User::register(new Login('user@example.test', 'password', ['admin']), 'User');
    $this->user->save();
});

it('isolates role permissions between tenants and users', function () {
    $otherTenant = Tenant::locate('other');
    $this->user->grant(
        Role::register($this->tenant, 'Reader', ['orders.view']),
        Role::system($otherTenant, 'Editor', [['orders.edit']]),
    );
    Role::register($this->tenant, 'Unassigned', ['orders.delete']);

    expect($this->user->able('orders.view'))
        ->toBeTrue()
        ->and($this->user->able('orders.edit'))
        ->toBeFalse()
        ->and($this->user->able('orders.edit', $otherTenant))
        ->toBeTrue()
        ->and($this->user->able('orders.view', $otherTenant->id))
        ->toBeFalse()
        ->and($this->user->able('orders.delete'))
        ->toBeFalse();
});

it('lists each tenant once and denies tenants without an assigned role', function () {
    $otherTenant = Tenant::locate('other');
    $this->user->grant(
        Role::register($this->tenant, 'Reader'),
        Role::register($this->tenant, 'Editor'),
    );

    expect($this->user->getTenants($this->panel)->modelKeys())
        ->toBe([$this->tenant->id])
        ->and($this->user->canAccessTenant($this->tenant))
        ->toBeTrue()
        ->and($this->user->canAccessTenant($otherTenant))
        ->toBeFalse();
});

it('uses the user status for panel access', function (UserStatus $status, bool $allowed) {
    $this->user->status = $status;

    expect($this->user->canAccessPanel($this->panel))->toBe($allowed);
})->with([
    [UserStatus::Active,   true],
    [UserStatus::Blocked,  false],
    [UserStatus::Archived, false],
]);

it('persists encrypted MFA state and supports disabling it', function () {
    $this->user->saveAppAuthenticationSecret('mfa-secret');
    $this->user->saveAppAuthenticationRecoveryCodes(['recovery-code']);
    $this->user->toggleEmailAuthentication(true);
    $this->user->refresh();

    expect($this->user->getAppAuthenticationSecret())
        ->toBe('mfa-secret')
        ->and($this->user->getAppAuthenticationRecoveryCodes())
        ->toBe(['recovery-code'])
        ->and($this->user->hasEmailAuthentication())
        ->toBeTrue()
        ->and($this->user->getRawOriginal('two_factor_secret'))
        ->not->toBe('mfa-secret')->and($this->user->getRawOriginal('two_factor_recovery_codes'))
        ->not->toContain('recovery-code')->and($this->user->toArray())
        ->not->toHaveKeys(['two_factor_secret', 'two_factor_recovery_codes']);

    $this->user->saveAppAuthenticationSecret(null);
    $this->user->saveAppAuthenticationRecoveryCodes(null);
    $this->user->toggleEmailAuthentication(false);
    $this->user->refresh();

    expect($this->user->getAppAuthenticationSecret())
        ->toBeNull()
        ->and($this->user->getAppAuthenticationRecoveryCodes())
        ->toBeNull()
        ->and($this->user->hasEmailAuthentication())
        ->toBeFalse();
});

it('archives active users and restores blocked or archived users', function (UserStatus $status, UserStatus $expected) {
    $this->user->update(['status' => $status]);

    StatusAction::make()->record($this->user)->call();

    expect($this->user->fresh()->status)->toBe($expected);
})->with([
    [UserStatus::Active,   UserStatus::Archived],
    [UserStatus::Blocked,  UserStatus::Active],
    [UserStatus::Archived, UserStatus::Active],
]);

it('replaces role permissions and resolves names ids and models', function () {
    $role = Role::register($this->tenant, 'Editor', ['obsolete'])->fresh();
    $permission = Permission::register('orders.view');
    $role->grant($permission->id, 'orders.edit');

    expect($role->fresh()->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['orders.edit', 'orders.view']);

    $role->fresh()->grant($permission);
    expect($role->fresh()->able($permission))->toBeTrue();

    $role->fresh()->grant();
    expect($role->fresh()->permissions)->toBeEmpty();
});
