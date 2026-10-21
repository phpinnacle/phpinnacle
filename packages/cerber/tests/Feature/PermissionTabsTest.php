<?php

use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Widgets\AccountWidget;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component as LivewireComponent;
use PHPinnacle\Cerber\Cerberus;
use PHPinnacle\Cerber\Forms\PermissionTabs;
use PHPinnacle\Cerber\Models\Permission;
use PHPinnacle\Cerber\Models\Role;
use PHPinnacle\Cerber\Resources\Users\UserResource;
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

it('hydrates grants into custom resource page and widget lists', function () {
    Filament::setCurrentPanel(
        Panel::make()
            ->id('admin')
            ->resources([UserResource::class])
            ->pages([Dashboard::class])
            ->widgets([AccountWidget::make()]),
    );
    Cerberus::guard('Custom', 'custom', ['manage_custom' => 'Manage custom', 'other_custom' => 'Other']);
    $role = new Role;
    $role->setRelation(
        'permissions',
        new Collection(array_map(
            fn (string $name) => new Permission(['name' => $name]),
            ['view_user', 'manage_custom', Dashboard::class, AccountWidget::class],
        )),
    );
    $livewire = new class extends LivewireComponent implements HasSchemas {
        use InteractsWithSchemas;

        public array $data = [];
    };
    $schema = Schema::make($livewire)->statePath('data')->record($role)->components([PermissionTabs::make()]);
    $schema->fill();
    $fields = collect($schema->getFlatFields())->keyBy(fn ($field) => $field->getName());

    foreach ([
        'permissions.user' => 'view_user',
        'permissions.custom' => 'manage_custom',
        'permissions.pages' => Dashboard::class,
        'permissions.widgets' => AccountWidget::class,
    ] as $name => $permission) {
        expect(array_values($fields[$name]->getState()))
            ->toBe([$permission])
            ->and($fields[$name]->isLabelHidden())
            ->toBeTrue()
            ->and($fields[$name]->isBulkToggleable())
            ->toBeTrue();
    }
});
