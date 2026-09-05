<?php

namespace PHPinnacle\Iconic;

use BladeUI\Icons\Factory as IconFactory;
use BladeUI\Icons\IconsManifest;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
use Livewire\Attributes\Renderless;

class IconPicker extends Select
{
    private const array PHOSPHOR_WEIGHTS = ['regular', 'bold', 'duotone', 'fill', 'light', 'thin'];

    /** @var list<string>|(Closure(): list<string>)|null */
    protected array|Closure|null $allowedIcons = null;

    /** @var list<string>|Closure(): list<string> */
    protected array|Closure $excludedIcons = [];

    protected int|Closure $iconColumns = 6;

    protected int|Closure $iconRows = 5;

    /** @var list<string>|Closure(): list<string> */
    protected array|Closure $sets = ['phosphor-icons'];

    protected array $excludeSuffix = ['-bold.svg', '-duotone.svg', '-fill.svg', '-light.svg', '-thin.svg'];

    protected array $excludePrefix = ['s-', 'm-'];

    /** @var list<string>|Closure(): list<string> */
    protected array|Closure $weights = ['regular'];

    public static function getIconLabel(string $icon): string
    {
        return view('phpinnacle-iconic::forms.icon-picker')->with('icon', $icon)->render();
    }

    /** @param list<string>|Closure(): list<string> $icons */
    public function allowIcons(array|Closure $icons): static
    {
        $this->allowedIcons = $icons;

        return $this;
    }

    /**
     * @param  array<string, int|Closure|null>|int|Closure|null  $columns
     */
    public function columns(array|int|Closure|null $columns = 6): static
    {
        if (is_array($columns) || $columns === null) {
            throw new \InvalidArgumentException('Icon picker columns must be a positive integer.');
        }

        $this->iconColumns = $columns;

        return $this;
    }

    /** @param list<string>|Closure(): list<string> $icons */
    public function excludeIcons(array|Closure $icons): static
    {
        $this->excludedIcons = $icons;

        return $this;
    }

    public function getIconColumns(): int
    {
        return $this->evaluatePositiveInteger($this->iconColumns, 'columns');
    }

    /**
     * @return array{
     *     options: array<int, array{label: string, value: string, isDisabled: bool}>,
     *     hasMore: bool
     * }
     */
    #[ExposedLivewireMethod]
    #[Renderless]
    public function getIconPageForJs(string $search, int $offset, array $preferred = []): array
    {
        $preferred = array_slice(
            array_values(array_unique(array_filter(
                $preferred,
                static fn ($icon) => is_string($icon),
            ))),
            0,
            105,
        );
        $page = $this->iconPage($search, max(0, $offset), $preferred);

        return [
            'options' => $this->transformOptionsForJs($page['options']),
            'hasMore' => $page['hasMore'],
        ];
    }

    public function getIconPageSize(): int
    {
        return $this->getIconColumns() * $this->getIconRows();
    }

    public function getIconRows(): int
    {
        return $this->evaluatePositiveInteger($this->iconRows, 'rows');
    }

    /** @param list<string>|Closure(): list<string> $sets */
    public function iconSets(array|Closure $sets): static
    {
        $this->sets = $sets;

        return $this;
    }

    public function rows(int|Closure $rows = 5): static
    {
        $this->iconRows = $rows;

        return $this;
    }

    /** @param list<string>|Closure(): list<string> $weights */
    public function weights(array|Closure $weights): static
    {
        $this->weights = $weights;

        return $this;
    }

    /** @return list<string> */
    protected function getIcons(): array
    {
        $registeredSets = App::make(IconFactory::class)->all();
        $manifest = App::make(IconsManifest::class)->getManifest($registeredSets);
        $selectedSets = $this->getIconSets();
        $weights = $this->getIconWeights();
        $excludedSuffixes = $this->getExcludedSuffixes($weights);

        return collect($manifest)
            ->filter(fn ($value, $set) => in_array($set, $selectedSets, true))
            ->flatMap(function (array $paths, string $set) use ($registeredSets, $weights, $excludedSuffixes) {
                $prefix = $registeredSets[$set]['prefix'];

                return collect($paths)
                    ->flatten()
                    ->filter(function (string $icon) use ($prefix, $weights, $excludedSuffixes) {
                        $fileName = Str::afterLast($icon, '.') . '.svg';

                        return (
                            !Str::startsWith($fileName, $this->excludePrefix)
                            && !Str::endsWith($fileName, $excludedSuffixes)
                            && ($prefix !== 'phosphor' || $this->hasPhosphorWeight($fileName, $weights))
                        );
                    })
                    ->map(fn (string $icon) => $prefix . '-' . $icon);
            })
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     options: array<string, string>,
     *     hasMore: bool
     * }
     */
    protected function iconPage(string $search, int $offset, array $preferred = []): array
    {
        $limit = $this->getIconPageSize();
        $icons = collect($this->getAvailableIcons());

        if ($search !== '') {
            $icons = $icons->filter(
                fn (string $icon) => Str::contains($icon, $search, ignoreCase: true),
            );
        }

        if ($preferred !== []) {
            $preference = array_flip($preferred);
            $icons = $icons->sortBy(fn (string $icon) => $preference[$icon] ?? count($preference));
        }

        $icons = $icons->slice($offset, $limit + 1);

        return [
            'options' => $icons
                ->take($limit)
                ->mapWithKeys(fn (string $icon) => [$icon => static::getIconLabel($icon)])
                ->all(),
            'hasMore' => $icons->count() > $limit,
        ];
    }

    protected function paginationAlpineExpression(): string
    {
        return (
            '$nextTick(() => window.phpinnacleIconPicker.mount($data.select, $wire, '
            . Js::from($this->getKey())
            . ', '
            . Js::from([
                'pageSize' => $this->getIconPageSize(),
                'addFavoriteLabel' => __('phpinnacle-iconic::forms.icon.add_favorite'),
                'loadMoreLabel' => __('phpinnacle-iconic::forms.icon.load_more'),
                'loadingLabel' => __('phpinnacle-iconic::forms.icon.loading'),
                'removeFavoriteLabel' => __('phpinnacle-iconic::forms.icon.remove_favorite'),
            ])
            . '))'
        );
    }

    /** @return array<string, string> */
    protected function searchIcons(string $search): array
    {
        return $this->iconPage($search, 0)['options'];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-iconic::forms.icon.label'))
            ->placeholder(__('phpinnacle-iconic::forms.icon.placeholder'))
            ->allowHtml()
            ->searchable()
            ->searchValues()
            ->searchLabels(false)
            ->searchDebounce(200)
            ->getSearchResultsUsing(
                static fn (IconPicker $component, string $search) => $component->searchIcons($search),
            )
            ->getOptionLabelUsing(fn (?string $value) => $value === null ? null : static::getIconLabel($value))
            ->extraAlpineAttributes(static fn (IconPicker $component) => [
                'x-init' => $component->paginationAlpineExpression(),
            ])
            ->extraAttributes(static function (IconPicker $component) {
                $maxHeight = 3.75 + ($component->getIconRows() * 2.625);

                return [
                    'class' => 'phpinnacle-icon-picker',
                    'style' => "--phpinnacle-icon-picker-columns: {$component->getIconColumns()}; --phpinnacle-icon-picker-max-height: {$maxHeight}rem",
                ];
            })
            ->optionsLimit(static fn (IconPicker $component) => $component->getIconPageSize());
    }

    private function evaluatePositiveInteger(int|Closure $value, string $name): int
    {
        $value = $this->evaluate($value);

        if (!is_int($value) || $value < 1) {
            throw new \InvalidArgumentException("Icon picker {$name} must be a positive integer.");
        }

        return $value;
    }

    /** @return list<string>|null */
    private function getAllowedIcons(): ?array
    {
        if ($this->allowedIcons === null) {
            return null;
        }

        /** @var list<string> $icons */
        $icons = $this->evaluate($this->allowedIcons);

        return $icons;
    }

    /** @return list<string> */
    private function getAvailableIcons(): array
    {
        $icons = collect($this->getIcons());
        $allowedIcons = $this->getAllowedIcons();

        if ($allowedIcons !== null) {
            $icons = $icons->filter(fn (string $icon) => in_array($icon, $allowedIcons, true));
        }

        $excludedIcons = $this->getExcludedIcons();

        if ($excludedIcons !== []) {
            $icons = $icons->reject(fn (string $icon) => in_array($icon, $excludedIcons, true));
        }

        return $icons->values()->all();
    }

    /** @return list<string> */
    private function getExcludedIcons(): array
    {
        /** @var list<string> $icons */
        $icons = $this->evaluate($this->excludedIcons);

        return $icons;
    }

    /**
     * @param  list<string>  $weights
     * @return list<string>
     */
    private function getExcludedSuffixes(array $weights): array
    {
        return array_values(array_filter(
            $this->excludeSuffix,
            static fn (string $suffix) => !in_array(substr($suffix, 1, -4), $weights, true),
        ));
    }

    /** @return list<string> */
    private function getIconSets(): array
    {
        /** @var list<string> $sets */
        $sets = $this->evaluate($this->sets);

        return $sets;
    }

    /** @return list<string> */
    private function getIconWeights(): array
    {
        /** @var list<string> $weights */
        $weights = $this->evaluate($this->weights);

        return $weights;
    }

    /** @param list<string> $weights */
    private function hasPhosphorWeight(string $fileName, array $weights): bool
    {
        foreach (self::PHOSPHOR_WEIGHTS as $weight) {
            if ($weight !== 'regular' && Str::endsWith($fileName, "-{$weight}.svg")) {
                return in_array($weight, $weights, true);
            }
        }

        return in_array('regular', $weights, true);
    }
}
