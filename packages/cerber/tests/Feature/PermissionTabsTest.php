<?php

use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component as LivewireComponent;
use PHPinnacle\Cerber\Cerberus;
use PHPinnacle\Cerber\Forms\PermissionTabs;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function () {
    Cerberus::$permissions = [];
});

it('merges custom guards into an existing permission tab', function () {
    Cerberus::guard('phpinnacle-cerber::permissions.group', 'custom', [
        'manage_custom' => 'Custom',
    ]);

    $tabs = collect(PermissionTabs::make()->getDefaultChildComponents())
        ->filter(fn (Tab $tab) => $tab->getLabel() === __('phpinnacle-cerber::permissions.group'));

    expect($tabs)
        ->toHaveCount(1)
        ->and($tabs->first()->getDefaultChildComponents())
        ->toHaveCount(1)
        ->and($tabs->first()->getBadge())
        ->toBe('1');
});

it('assigns stable keys to permission tabs', function () {
    $livewire = new class extends LivewireComponent implements HasSchemas {
        use InteractsWithSchemas;
    };
    $tabs = PermissionTabs::make();

    Schema::make($livewire)->components([$tabs])->fill();

    $keys = collect($tabs->getDefaultChildComponents())
        ->map(fn (Tab $tab) => $tab->getKey(isAbsolute: false))
        ->values();

    expect($keys->all())
        ->toBe(
            $keys->keys()->map(fn (int $index) => "permission-tab-{$index}")->all(),
        );
});
