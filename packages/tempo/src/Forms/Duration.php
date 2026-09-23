<?php

namespace PHPinnacle\Tempo\Forms;

use Closure;
use Filament\Forms\Components\Field;
use PHPinnacle\Tempo\Rules\ValidDuration;

class Duration extends Field
{
    private const array DEFAULT_UNITS = ['day', 'hour', 'minute', 'second'];

    private const array DEFAULT_QUICK_VALUES = [
        'day' => [0, 1, 2, 7, 14, 30],
        'hour' => [0, 1, 2, 4, 8, 12],
        'minute' => [0, 5, 10, 15, 30, 45],
        'second' => [0, 5, 10, 15, 30, 45],
    ];

    protected string $view = 'phpinnacle-tempo::forms.duration';

    /** @var 'seconds'|'string'|Closure */
    protected string|Closure $storageFormat = 'seconds';

    /** @var non-empty-list<'day'|'hour'|'minute'|'second'>|Closure */
    protected array|Closure $units = self::DEFAULT_UNITS;

    /** @var array<'day'|'hour'|'minute'|'second', list<int>>|Closure */
    protected array|Closure $quickValues = [];

    /** @var array<string, string>|Closure */
    protected array|Closure $presets = [
        'fifteen_minutes' => '15m',
        'hour' => '1h',
        'day' => '1d',
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->rules(fn () => [new ValidDuration($this->getStorageFormat(), $this->getUnits())]);
    }

    /** @param 'seconds'|'string'|Closure $format */
    public function storeAs(string|Closure $format): static
    {
        $this->storageFormat = $format;

        return $this;
    }

    /** @return 'seconds'|'string' */
    public function getStorageFormat(): string
    {
        return $this->evaluate($this->storageFormat);
    }

    /** @param non-empty-list<'day'|'hour'|'minute'|'second'>|Closure $units */
    public function units(array|Closure $units): static
    {
        $this->units = $units;

        return $this;
    }

    /** @return non-empty-list<'day'|'hour'|'minute'|'second'> */
    public function getUnits(): array
    {
        return $this->evaluate($this->units);
    }

    /** @param array<'day'|'hour'|'minute'|'second', list<int>>|Closure $values */
    public function quickValues(array|Closure $values): static
    {
        $this->quickValues = $values;

        return $this;
    }

    /** @return array<'day'|'hour'|'minute'|'second', list<int>> */
    public function getQuickValues(): array
    {
        return array_replace(self::DEFAULT_QUICK_VALUES, $this->evaluate($this->quickValues));
    }

    /** @param array<string, string>|Closure $presets */
    public function presets(array|Closure $presets): static
    {
        $this->presets = $presets;

        return $this;
    }

    /** @return array<string, string> */
    public function getPresets(): array
    {
        return $this->evaluate($this->presets);
    }
}
