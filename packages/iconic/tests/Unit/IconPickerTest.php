<?php

use Filament\Forms\Components\Repeater;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\ViewErrorBag;
use Livewire\Component as LivewireComponent;
use PHPinnacle\Iconic\IconPicker;
use Tests\TestCase;

uses(TestCase::class);

function mountedIconPicker(?Closure $configure = null): IconPicker
{
    view()->share('errors', new ViewErrorBag);

    $livewire = new class extends LivewireComponent implements HasSchemas {
        use InteractsWithSchemas;

        /** @var array<string, mixed> */
        public array $data = [];
    };

    $livewire->setId('icon-picker-test');
    $livewire->setName('icon-picker-test');

    $picker = IconPicker::make('icon');
    $configure?->__invoke($picker);

    Schema::make($livewire)
        ->statePath('data')
        ->components([$picker])
        ->fill();

    return $picker;
}

it('discovers regular Phosphor icons only when searched', function () {
    $picker = IconPicker::make('icon');
    $options = $picker->getSearchResults('phosphor-money');

    expect($picker->getOptions())
        ->toBe([])
        ->and($options)
        ->toHaveKey('phosphor-money')
        ->not
        ->toHaveKey('phosphor-money-bold')
        ->and($options['phosphor-money'])
        ->toContain('<svg');
});

it('renders an icon label through the package view', function () {
    expect(IconPicker::getIconLabel('phosphor-money'))->toContain('<svg');
});

it('discovers plain icon names before rendering a page', function () {
    $picker = new class('icon') extends IconPicker {
        /** @return list<string> */
        public function discoveredIcons(): array
        {
            return $this->getIcons();
        }
    };

    $icons = $picker->discoveredIcons();

    expect($icons)
        ->toContain('phosphor-money')
        ->and(collect($icons)->contains(fn (string $icon) => str_contains($icon, '<svg')))
        ->toBeFalse();
});

it('discovers configured icon sets independently', function () {
    $phosphorIcons = IconPicker::make('icon')->getSearchResults('phosphor-money');
    $heroIcons = IconPicker::make('icon')
        ->iconSets(['heroicons'])
        ->getSearchResults('heroicon-o-heart');

    expect($phosphorIcons)
        ->toHaveKey('phosphor-money')
        ->and($heroIcons)
        ->toHaveKey('heroicon-o-heart')
        ->not->toHaveKey('phosphor-money');
});

it('discovers configured Phosphor weights', function () {
    $icons = IconPicker::make('icon')
        ->weights(fn () => ['bold'])
        ->allowIcons(['phosphor-heart', 'phosphor-heart-bold'])
        ->getSearchResults('heart');

    expect($icons)
        ->toHaveKey('phosphor-heart-bold')
        ->not->toHaveKey('phosphor-heart');
});

it('restricts available icons', function () {
    $icons = IconPicker::make('icon')
        ->allowIcons(fn () => ['phosphor-heart', 'phosphor-money'])
        ->excludeIcons(['phosphor-heart'])
        ->getSearchResults('phosphor');

    expect($icons)
        ->toHaveKey('phosphor-money')
        ->not->toHaveKey('phosphor-heart');
});

it('allows an application to disable every icon', function () {
    expect(IconPicker::make('icon')->allowIcons([])->getSearchResults('phosphor'))->toBe([]);
});

it('loads preferred icons first', function () {
    $page = IconPicker::make('icon')
        ->columns(2)
        ->rows(1)
        ->allowIcons(['phosphor-airplane', 'phosphor-heart', 'phosphor-money'])
        ->getIconPageForJs('', 0, ['phosphor-money']);

    expect($page['options'])
        ->toHaveCount(2)
        ->and($page['options'][0]['value'])
        ->toBe('phosphor-money');
});

it('configures icon grid columns', function () {
    $picker = IconPicker::make('icon');

    expect($picker->getIconColumns())
        ->toBe(6)
        ->and($picker->columns(8)->getIconColumns())
        ->toBe(8)
        ->and($picker->columns(fn () => 4)->getIconColumns())
        ->toBe(4);
});

it('loads one configured page of icons at a time', function () {
    $picker = IconPicker::make('icon')
        ->columns(3)
        ->rows(2);

    $firstPage = $picker->getIconPageForJs('', 0);
    $secondPage = $picker->getIconPageForJs('', 6);

    expect($picker->getIconRows())
        ->toBe(2)
        ->and($firstPage['options'])
        ->toHaveCount(6)
        ->and($firstPage['hasMore'])
        ->toBeTrue()
        ->and($secondPage['options'])
        ->toHaveCount(6)
        ->and($secondPage['options'][0]['value'])
        ->not->toBe($firstPage['options'][0]['value']);
});

it('renders the paginated icon loader without embedding icon options', function () {
    $picker = mountedIconPicker(
        fn (IconPicker $picker) => $picker
            ->columns(3)
            ->rows(2),
    );
    $html = $picker->toHtml();

    expect($picker->getOptions())
        ->toBe([])
        ->and($html)
        ->toContain('phpinnacleIconPicker.mount')
        ->toContain('Add to favorites')
        ->toContain('--phpinnacle-icon-picker-max-height: 9rem');
});

it('renders the current picker clone inside a repeater', function () {
    view()->share('errors', new ViewErrorBag);

    $livewire = new class extends LivewireComponent implements HasSchemas {
        use InteractsWithSchemas;

        /** @var array<string, mixed> */
        public array $data = [];
    };

    $livewire->setId('repeated-icon-picker-test');
    $livewire->setName('repeated-icon-picker-test');

    $schema = Schema::make($livewire)
        ->statePath('data')
        ->components([
            Repeater::make('styles')
                ->schema([
                    IconPicker::make('icon')
                        ->columns(3)
                        ->rows(2),
                ]),
        ])
        ->fill([
            'styles' => [
                ['icon' => null],
            ],
        ]);

    expect($schema->toHtml())
        ->toContain('phpinnacleIconPicker.mount')
        ->toContain('--phpinnacle-icon-picker-max-height: 9rem');
});

it('does not resolve rendering state from an unmounted source component', function () {
    view()->share('errors', new ViewErrorBag);

    $livewire = new class extends LivewireComponent implements HasSchemas {
        use InteractsWithSchemas;

        /** @var array<string, mixed> */
        public array $data = [];
    };

    $livewire->setId('cloned-icon-picker-test');
    $livewire->setName('cloned-icon-picker-test');

    $source = IconPicker::make('icon')
        ->columns(3)
        ->rows(2);
    $picker = clone $source;

    Schema::make($livewire)
        ->statePath('data')
        ->components([$picker])
        ->fill();

    expect($picker->toHtml())
        ->toContain('phpinnacleIconPicker.mount')
        ->toContain('--phpinnacle-icon-picker-max-height: 9rem');
});

it('rejects invalid icon grid columns', function () {
    IconPicker::make('icon')->columns(0)->getIconColumns();
})->throws(InvalidArgumentException::class, 'Icon picker columns must be a positive integer.');

it('rejects invalid icon grid rows', function () {
    IconPicker::make('icon')->rows(0)->getIconRows();
})->throws(InvalidArgumentException::class, 'Icon picker rows must be a positive integer.');
