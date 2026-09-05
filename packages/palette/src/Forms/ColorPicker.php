<?php

namespace PHPinnacle\Palette\Forms;

use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Filament\Support\Facades\FilamentColor;
use PHPinnacle\Palette\Color;
use PHPinnacle\Palette\ColorFormat;
use PHPinnacle\Palette\ColorSource;

class ColorPicker extends Field
{
    use HasPlaceholder;

    protected string $view = 'phpinnacle-palette::forms.color-picker';

    protected ColorFormat $colorFormat = ColorFormat::Hex;

    /** @var array<ColorSource> */
    protected array $sources = [ColorSource::Custom];

    /** @var array<int> */
    protected array $shades = Color::SHADES;

    protected bool $isWide = false;

    public function format(ColorFormat $format): static
    {
        $this->colorFormat = $format;

        if ($format === ColorFormat::Semantic) {
            $this->sources = [ColorSource::Filament];
        }

        return $this;
    }

    public function getColorFormat(): ColorFormat
    {
        return $this->colorFormat;
    }

    public function enable(ColorSource ...$sources): static
    {
        foreach ($sources as $source) {
            if (!in_array($source, $this->sources, true)) {
                $this->sources[] = $source;
            }
        }

        return $this;
    }

    public function disable(ColorSource ...$sources): static
    {
        $this->sources = array_values(array_filter(
            $this->sources,
            static fn (ColorSource $source) => !in_array($source, $sources, true),
        ));

        return $this;
    }

    /**
     * @return array<ColorSource>
     */
    public function getSources(): array
    {
        return array_values(array_filter(
            $this->sources,
            fn (ColorSource $source) => $this->colorFormat->supports($source),
        ));
    }

    public function hasSource(ColorSource $source): bool
    {
        return in_array($source, $this->getSources(), true);
    }

    public function shades(int $min = 50, int $max = 950): static
    {
        $this->shades = array_values(array_filter(
            Color::SHADES,
            static fn (int $shade) => $shade >= $min && $shade <= $max,
        ));

        return $this;
    }

    public function wide(bool $condition = true): static
    {
        $this->isWide = $condition;

        return $this;
    }

    public function isWide(): bool
    {
        return $this->isWide;
    }

    /**
     * @return array<int>
     */
    public function getShades(): array
    {
        return $this->shades;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getTailwindColors(): array
    {
        return array_map(
            function (array $palette) {
                $colors = [];

                foreach ($this->shades as $shade) {
                    $colors[$shade] = Color::hex($palette, $shade);
                }

                return $colors;
            },
            Color::TAILWIND,
        );
    }

    /**
     * @return array<string, string>
     */
    public function getThemeColors(): array
    {
        return array_map(
            Color::hex(...),
            FilamentColor::getColors(),
        );
    }

    public function getThemeValue(string $name, string $color): string
    {
        return $this->colorFormat === ColorFormat::Semantic
            ? $name
            : $color;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->placeholder(__('phpinnacle-palette::color-picker.placeholder'))
            ->formatStateUsing(fn (?string $state) => $this->colorFormat->hydrate($state))
            ->dehydrateStateUsing(fn (?string $state) => $this->colorFormat->dehydrate($state))
            ->regex(fn () => $this->colorFormat->pickerPattern(array_keys(FilamentColor::getColors())));
    }
}
