<?php

namespace PHPinnacle\Iconic;

use BladeUI\Icons\Factory as IconFactory;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
use Livewire\Attributes\Renderless;

class IconPicker extends Select
{
    protected int|Closure $iconColumns = 6;

    protected int|Closure $iconRows = 5;

    protected array $sets = ['phosphor-icons'];

    protected array $excludeSuffix = ['-bold.svg', '-duotone.svg', '-fill.svg', '-light.svg', '-thin.svg'];

    protected array $excludePrefix = ['s-', 'm-'];

    public static function getIconLabel(string $icon): string
    {
        return view('phpinnacle-iconic::forms.icon-picker')->with('icon', $icon)->render();
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

    public function getIconColumns(): int
    {
        $columns = $this->evaluate($this->iconColumns);

        if (!is_int($columns) || $columns < 1) {
            throw new \InvalidArgumentException('Icon picker columns must be a positive integer.');
        }

        return $columns;
    }

    /**
     * @return array{
     *     options: array<int, array{label: string, value: string, isDisabled: bool}>,
     *     hasMore: bool
     * }
     */
    #[ExposedLivewireMethod]
    #[Renderless]
    public function getIconPageForJs(string $search, int $offset): array
    {
        $page = $this->iconPage($search, max(0, $offset));

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
        $rows = $this->evaluate($this->iconRows);

        if (!is_int($rows) || $rows < 1) {
            throw new \InvalidArgumentException('Icon picker rows must be a positive integer.');
        }

        return $rows;
    }

    public function rows(int|Closure $rows = 5): static
    {
        $this->iconRows = $rows;

        return $this;
    }

    protected function getIcons(): array
    {
        return Cache::rememberForever(
            'icons-select',
            fn () => collect(App::make(IconFactory::class)->all())
                ->filter(fn ($value, $key) => in_array($key, $this->sets))
                ->map(
                    fn ($set) => collect($set['paths'])
                        ->map(
                            fn ($path) => collect(File::files($path))
                                ->filter(fn ($file) => Str::endsWith($file, '.svg'))
                                ->reject(
                                    fn ($file) => (
                                        Str::startsWith($file->getFileName(), $this->excludePrefix)
                                        || Str::endsWith($file->getFileName(), $this->excludeSuffix)
                                    ),
                                )
                                ->map(fn ($file) => $set['prefix'] . '-' . $file->getFilenameWithoutExtension()),
                        ),
                )
                ->flatten()
                ->mapWithKeys(fn (string $icon) => [$icon => static::getIconLabel($icon)])
                ->all(),
        );
    }

    /**
     * @return array{
     *     options: array<string, string>,
     *     hasMore: bool
     * }
     */
    protected function iconPage(string $search, int $offset): array
    {
        $limit = $this->getIconPageSize();
        $icons = collect($this->getIcons());

        if ($search !== '') {
            $icons = $icons->filter(
                fn (string $label, string $icon) => Str::contains($icon, $search, ignoreCase: true),
            );
        }

        $icons = $icons->slice($offset, $limit + 1);

        return [
            'options' => $icons->take($limit)->all(),
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
                'loadMoreLabel' => __('phpinnacle-iconic::forms.icon.load_more'),
                'loadingLabel' => __('phpinnacle-iconic::forms.icon.loading'),
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
}
