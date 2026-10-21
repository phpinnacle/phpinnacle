<?php

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Cerber\CerberPlugin;
use PHPinnacle\Cerber\Cerberus;
use PHPinnacle\Cerber\Models\Tenant;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->scopedModels = Cerberus::$scoped;
    $this->resourcesScopedToTenant = Resource::isScopedToTenant();
    Cerberus::$scoped = [];
    Cerberus::registerToScope(TenantScopeRecord::class);

    Schema::create('tenant_scope_records', function (Blueprint $table) {
        $table->id();
        $table->uuid('tenant_id')->nullable();
    });
});

afterEach(function () {
    Cerberus::$scoped = $this->scopedModels;
    Resource::scopeToTenant($this->resourcesScopedToTenant);
});

it('registers one creating listener across repeated panel boots and preserves other listeners', function () {
    $calls = 0;
    TenantScopeRecord::creating(function () use (&$calls) {
        $calls++;
    });
    $plugin = CerberPlugin::make()->tenancy();
    $plugin->boot(Panel::make()->id('first'));
    $event = 'eloquent.creating: ' . TenantScopeRecord::class;
    $listenerCount = count(Event::getRawListeners()[$event]);

    $plugin->boot(Panel::make()->id('second'));
    $plugin->boot(Panel::make()->id('first'));

    expect(count(Event::getRawListeners()[$event]))->toBe($listenerCount);
    TenantScopeRecord::query()->create();
    expect($calls)->toBe(1);
});

it('resolves the tenant when creating a record and preserves explicit assignments', function () {
    Cerberus::registerScoped();
    $first = new Tenant;
    $first->id = '00000000-0000-0000-0000-000000000001';
    $second = new Tenant;
    $second->id = '00000000-0000-0000-0000-000000000002';

    Filament::setTenant($first, isQuiet: true);
    expect(TenantScopeRecord::query()->create()->tenant_id)->toBe($first->id);

    Filament::setTenant($second, isQuiet: true);
    Cerberus::registerScoped();
    expect(TenantScopeRecord::query()->create()->tenant_id)
        ->toBe($second->id)
        ->and(TenantScopeRecord::query()->create(['tenant_id' => $first->id])->tenant_id)
        ->toBe($first->id);

    Filament::setTenant(null, isQuiet: true);
    $record = TenantScopeRecord::query()->create();
    expect($record->tenant_id)
        ->toBe(Tenant::DEFAULT)
        ->and($record->tenant()->getRelated())
        ->toBeInstanceOf(Tenant::class);
});

it('registers models added after the first boot', function () {
    Cerberus::registerScoped();
    Cerberus::registerToScope(LateTenantScopeRecord::class);
    Cerberus::registerScoped();

    expect(LateTenantScopeRecord::query()->create()->tenant_id)
        ->toBe(Tenant::DEFAULT)
        ->and(count(Event::getRawListeners()['eloquent.creating: ' . TenantScopeRecord::class]))
        ->toBe(1);
});

it('registers again when model event listeners have been cleared', function () {
    Cerberus::registerScoped();
    TenantScopeRecord::flushEventListeners();
    Cerberus::registerScoped();

    expect(TenantScopeRecord::query()->create()->tenant_id)->toBe(Tenant::DEFAULT);
});

class TenantScopeRecord extends Model
{
    public $timestamps = false;

    protected $table = 'tenant_scope_records';

    protected $guarded = [];
}

class LateTenantScopeRecord extends TenantScopeRecord {}
