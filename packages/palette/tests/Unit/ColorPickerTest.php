<?php

use Filament\Schemas\Components\Component;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color as FilamentColorPalette;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\ViewErrorBag;
use Livewire\Component as LivewireComponent;
use PHPinnacle\Palette\Color;
use PHPinnacle\Palette\ColorFormat;
use PHPinnacle\Palette\ColorSource;
use PHPinnacle\Palette\Forms\ColorPicker;
use Tests\TestCase;

uses(TestCase::class);

function palette_color_picker(?Closure $configure = null, ?string $state = null): ColorPicker
{
    view()->share('errors', new ViewErrorBag);

    $livewire = new class extends LivewireComponent implements HasSchemas {
        use InteractsWithSchemas;

        /** @var array<string, mixed> */
        public array $data = [];
    };

    $livewire->setId('palette-color-picker-test');
    $livewire->setName('palette-color-picker-test');

    $picker = ColorPicker::make('color');
    $configure?->__invoke($picker);

    Schema::make($livewire)
        ->statePath('data')
        ->components([$picker])
        ->fill($state === null ? [] : ['color' => $state]);

    return $picker;
}

it('offers the complete Tailwind palette as hex colors', function () {
    $colors = palette_color_picker(
        fn (ColorPicker $picker) => $picker->shades(),
    )->getTailwindColors();

    expect($colors)
        ->toHaveKeys(['slate', 'red', 'emerald', 'blue', 'violet', 'rose'])
        ->toHaveCount(22)
        ->and($colors['blue'])
        ->toHaveKeys([50, 500, 950])
        ->and($colors['blue'][500])
        ->toBe(Color::hex(Color::Blue));
});

it('can use only the custom source', function () {
    $picker = palette_color_picker(
        fn (ColorPicker $picker) => $picker->disable(
            ColorSource::Tailwind,
            ColorSource::Filament,
        ),
    );

    expect($picker->getSources())
        ->toBe([ColorSource::Custom])
        ->and($picker->hasSource(ColorSource::Tailwind))
        ->toBeFalse()
        ->and($picker->hasSource(ColorSource::Filament))
        ->toBeFalse()
        ->and($picker->hasSource(ColorSource::Custom))
        ->toBeTrue()
        ->and($picker->getColorFormat())
        ->toBe(ColorFormat::Hex);
});

it('enables and disables color sources fluently', function () {
    $picker = palette_color_picker(
        fn (ColorPicker $picker) => $picker
            ->enable(
                ColorSource::Tailwind,
                ColorSource::Filament,
                ColorSource::Tailwind,
            )
            ->disable(ColorSource::Custom),
    );

    expect($picker->getSources())->toBe([
        ColorSource::Tailwind,
        ColorSource::Filament,
    ]);
});

it('limits the displayed Tailwind shades', function () {
    $picker = palette_color_picker(
        fn (ColorPicker $picker) => $picker
            ->enable(ColorSource::Tailwind)
            ->shades(min: 125, max: 875),
    );

    expect($picker->getShades())
        ->toBe([200, 300, 400, 500, 600, 700, 800])
        ->and($picker->getTailwindColors()['blue'])
        ->toHaveKeys([200, 300, 400, 500, 600, 700, 800])
        ->and($picker->getTailwindColors()['blue'])
        ->toHaveCount(7);
});

it('can make the popover as wide as the input', function () {
    $widePicker = palette_color_picker(
        fn (ColorPicker $picker) => $picker->wide(),
    );
    $regularPicker = palette_color_picker(
        fn (ColorPicker $picker) => $picker->wide(false),
    );

    expect($widePicker->isWide())
        ->toBeTrue()
        ->and($widePicker->toHtml())
        ->toContain('is-wide')
        ->toContain('wide: true')
        ->and($regularPicker->isWide())
        ->toBeFalse()
        ->and($regularPicker->toHtml())
        ->not->toContain('is-wide');
});

it('uses colors registered in the Filament theme', function () {
    FilamentColor::register([
        'brand' => FilamentColorPalette::Fuchsia,
    ]);

    $picker = palette_color_picker();
    $brand = Color::hex(FilamentColorPalette::Fuchsia);

    expect($picker->getThemeColors())
        ->toHaveKey('brand', $brand)
        ->and($picker->getThemeValue('brand', $brand))
        ->toBe($brand);
});

it('renders only enabled color sources', function () {
    $html = palette_color_picker(
        fn (ColorPicker $picker) => $picker->disable(
            ColorSource::Tailwind,
            ColorSource::Filament,
        ),
    )->toHtml();
    $allSourcesHtml = palette_color_picker(
        fn (ColorPicker $picker) => $picker
            ->enable(ColorSource::Tailwind, ColorSource::Filament),
    )->toHtml();

    expect($html)
        ->toContain('phpinnacle-palette-color-picker')
        ->toContain('phpinnacle-palette-color-picker__input')
        ->toMatch('/\\sx-load\\s+x-load-src=/')
        ->not->toContain('x-load="visible"')->toContain(__('phpinnacle-palette::color-picker.custom'))->toContain(__(
            'phpinnacle-palette::color-picker.reset',
        ))->toContain('type="color"')
        ->not->toContain(__('phpinnacle-palette::color-picker.tailwind'))
        ->not->toContain(__('phpinnacle-palette::color-picker.theme'))->and($allSourcesHtml)->toContain(__(
            'phpinnacle-palette::color-picker.tailwind',
        ))->toContain(__('phpinnacle-palette::color-picker.theme'))->toContain(__(
            'phpinnacle-palette::color-picker.custom',
        ));
});

it('dehydrates colors in the configured format', function (
    ColorFormat $format,
    string $expected,
) {
    $picker = palette_color_picker(
        fn (ColorPicker $picker) => $picker->format($format),
        $expected,
    );

    expect($picker->getState())
        ->toBe('#ff0000')
        ->and($picker->getStateToDehydrate($picker->getState()))
        ->toBe(['data.color' => $expected])
        ->and($format->hydrate($expected))
        ->toBe('#ff0000');
})->with([
    'hex' => [ColorFormat::Hex, '#ff0000'],
    'rgba' => [ColorFormat::Rgba, 'rgba(255,0,0,1.00)'],
    'hsl' => [ColorFormat::Hsl, 'hsl(0,100%,50%)'],
]);

it('preserves the alpha channel in RGBA format', function () {
    $picker = palette_color_picker(
        fn (ColorPicker $picker) => $picker->format(ColorFormat::Rgba),
        'rgba(255,0,0,0.50)',
    );

    expect($picker->getState())
        ->toBe('#ff000080')
        ->and($picker->getStateToDehydrate($picker->getState()))
        ->toBe(['data.color' => 'rgba(255,0,0,0.50)'])
        ->and($picker->getRegexPattern())
        ->toBe('/^#[0-9a-fA-F]{6}(?:[0-9a-fA-F]{2})?$/')
        ->and($picker->toHtml())
        ->toContain('type="range"');
});

it('stores Filament aliases in semantic format', function () {
    FilamentColor::register([
        'brand' => FilamentColorPalette::Fuchsia,
    ]);

    $picker = palette_color_picker(
        fn (ColorPicker $picker) => $picker->format(ColorFormat::Semantic),
    );

    expect($picker->getSources())
        ->toBe([ColorSource::Filament])
        ->and($picker->getThemeValue('brand', '#d946ef'))
        ->toBe('brand')
        ->and($picker->getStateToDehydrate('brand'))
        ->toBe(['data.color' => 'brand']);
});

it('validates the picker state for its configured format', function () {
    FilamentColor::register([
        'brand' => FilamentColorPalette::Fuchsia,
    ]);

    $picker = palette_color_picker();
    $semantic = palette_color_picker(
        fn (ColorPicker $picker) => $picker->format(ColorFormat::Semantic),
    );

    expect($picker)
        ->toBeInstanceOf(Component::class)
        ->and($picker->getRegexPattern())
        ->toBe('/^#[0-9a-fA-F]{6}$/')
        ->and($semantic->getRegexPattern())
        ->toContain('primary')
        ->toContain('brand');
});
