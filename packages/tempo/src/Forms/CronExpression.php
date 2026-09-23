<?php

namespace PHPinnacle\Tempo\Forms;

use Closure;
use Filament\Forms\Components\Field;
use PHPinnacle\Tempo\Rules\ValidCronExpression;

class CronExpression extends Field
{
    private const array DEFAULT_GRID_COLUMNS = [
        'minute' => 6,
        'hour' => 4,
        'day' => 4,
        'month' => 3,
        'weekday' => 2,
    ];

    protected string $view = 'phpinnacle-tempo::forms.cron-expression';

    /** @var bool|Closure(): bool */
    protected bool|Closure $showDescription = false;

    /** @var bool|Closure(): bool */
    protected bool|Closure $showDayOfWeek = true;

    /** @var 'visual'|'expression'|Closure */
    protected string|Closure $defaultMode = 'visual';

    /** @var array<string, string>|Closure */
    protected array|Closure $presets = [
        'minute' => '* * * * *',
        'five_minutes' => '*/5 * * * *',
        'hourly' => '0 * * * *',
        'daily' => '0 9 * * *',
        'weekly' => '0 9 * * 1',
        'weekdays' => '0 9 * * 1-5',
        'three_days' => '0 9 * * 1,3,5',
        'monthly' => '0 9 1 * *',
    ];

    /** @var array<string, int>|Closure */
    protected array|Closure $gridColumns = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->rules([new ValidCronExpression]);
    }

    /** @param array<string, int>|Closure $columns */
    public function gridColumns(array|Closure $columns): static
    {
        $this->gridColumns = is_array($columns) && is_array($this->gridColumns)
            ? array_replace($this->gridColumns, $columns)
            : $columns;

        return $this;
    }

    /** @return array<string, int> */
    public function getGridColumns(): array
    {
        return array_replace(self::DEFAULT_GRID_COLUMNS, $this->evaluate($this->gridColumns));
    }

    /** @param bool|Closure(): bool $show */
    public function showDescription(bool|Closure $show = true): static
    {
        $this->showDescription = $show;

        return $this;
    }

    public function showsDescription(): bool
    {
        return $this->evaluate($this->showDescription);
    }

    /** @param bool|Closure(): bool $show */
    public function showDayOfWeek(bool|Closure $show = true): static
    {
        $this->showDayOfWeek = $show;

        return $this;
    }

    public function showsDayOfWeek(): bool
    {
        return $this->evaluate($this->showDayOfWeek);
    }

    /** @param 'visual'|'expression'|Closure $mode */
    public function defaultMode(string|Closure $mode): static
    {
        $this->defaultMode = $mode;

        return $this;
    }

    /** @return 'visual'|'expression' */
    public function getDefaultMode(): string
    {
        return $this->evaluate($this->defaultMode);
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
