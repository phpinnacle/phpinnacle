<?php

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\PanelRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use PHPinnacle\Cerber\CerberPlugin;
use PHPinnacle\Cerber\Cerberus;
use PHPinnacle\Cerber\Models\Login;
use PHPinnacle\Cerber\Models\Role;
use PHPinnacle\Cerber\Models\Tenant;
use PHPinnacle\Cerber\Models\User;
use PHPinnacle\Cerber\Resources\Roles\Pages\CreateRole;
use PHPinnacle\Cerber\Resources\Roles\Pages\EditRole;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    $this->artisan('migrate')->assertSuccessful();
    $this->tenant = Tenant::default('default');
    config()->set('auth.providers.users.model', User::class);

    $panel = Panel::make()
        ->id('admin')
        ->path('admin')
        ->default()
        ->plugin(CerberPlugin::make()->withoutUsers()->withoutProviders());
    app(PanelRegistry::class)->register($panel);
    Filament::setCurrentPanel($panel);
    require base_path('vendor/filament/filament/routes/web.php');
    Route::getRoutes()->refreshNameLookups();

    $user = User::register(new Login('admin@example.test', 'password', ['admin']), 'Admin');
    $user->save();
    $this->actingAs($user);
    Gate::before(fn () => true);
    Cerberus::guard('Custom', 'custom', ['orders.view' => 'View orders', 'orders.edit' => 'Edit orders']);
});

afterEach(function () {
    Cerberus::$permissions = [];
});

it('creates a role before permissions are configured on the edit page', function () {
    Livewire::test(CreateRole::class)
        ->fillForm(['name' => 'Editor', 'description' => '', 'is_active' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    $role = Role::query()->where('name', 'Editor')->sole();
    expect($role->tenant_id)->toBe($this->tenant->id)->and($role->permissions)->toBeEmpty();
});

it('hydrates and replaces role grants through the edit page', function () {
    $role = Role::register($this->tenant, 'Editor', ['orders.view']);

    Livewire::test(EditRole::class, ['record' => $role->id])
        ->assertFormSet(['permissions.custom' => ['orders.view']])
        ->fillForm(['permissions.custom' => ['orders.edit']])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($role->fresh()->permissions->pluck('name')->all())->toBe(['orders.edit']);
});
