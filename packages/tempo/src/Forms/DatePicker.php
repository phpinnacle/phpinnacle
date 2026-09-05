<?php

namespace PHPinnacle\Tempo\Forms;

use Closure;
use DanHarrin\DateFormatConverter\Converter;
use DateTimeInterface;
use Filament\Forms\Components\Concerns;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Contracts\HasAffixActions;
use Filament\Support\Concerns\HasExtraAlpineAttributes;
use Illuminate\Support\Facades\Date;

class DatePicker extends Field implements HasAffixActions
{
    use Concerns\CanBeReadOnly;
    use Concerns\HasAffixes;
    use Concerns\HasExtraInputAttributes;
    use Concerns\HasPlaceholder;
    use HasExtraAlpineAttributes;

    private const string JS_FORMAT = 'moment.js';

    private const string MODE_DATE = 'date';

    private const string MODE_TIME = 'time';

    private const string MODE_DATETIME = 'datetime';

    private const string MODE_RANGE = 'range';

    protected string $view = 'phpinnacle-tempo::forms.date-picker';

    protected string $mode = self::MODE_DATE;

    protected Closure|string|null $dateFormat = null;

    protected Closure|string|null $timeFormat = null;

    protected Closure|string|null $locale = null;

    protected Closure|string|null $timezone = null;

    protected Closure|DateTimeInterface|null $minDate = null;

    protected Closure|DateTimeInterface|null $maxDate = null;

    protected Closure|bool $autoclose = true;

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->prefixIcon('phosphor-calendar-dot')
            ->separator(' - ')
            ->placeholder(function (self $component) {
                $default = $component->getDefaultState();
                $format = $component->getMaskFormat();

                return $default !== null
                    ? Date::parse($default, $component->getTimezone())->format($format)
                    : $this->getMomentMaskFormat();
            });

        //            ->stateCast(function (self $component) {
        //                $format = $component->getMaskFormat();
        //                $cast = new DateTimeStateCast(
        //                    format: $format,
        //                    internalFormat: DATE_ATOM,
        //                    timezone: $component->getTimezone(),
        //                );
        //
        //                return match ($component->getMode()) {
        //                    self::MODE_DATE, self::MODE_TIME, self::MODE_DATETIME => $cast,
        //                    self::MODE_RANGE => new DateRangeStateCast($cast, $this->getSeparator()),
        //                };
        //            })
    }

    public function date(): static
    {
        $this->mode = self::MODE_DATE;

        return $this;
    }

    public function time(): static
    {
        $this->mode = self::MODE_TIME;

        return $this;
    }

    public function datetime(): static
    {
        $this->mode = self::MODE_DATETIME;

        return $this;
    }

    public function range(): static
    {
        $this->mode = self::MODE_RANGE;

        return $this;
    }

    public function dateFormat(Closure|string|null $format): static
    {
        $this->dateFormat = $format;

        return $this;
    }

    public function timeFormat(Closure|string|null $format): static
    {
        $this->timeFormat = $format;

        return $this;
    }

    public function locale(Closure|string|null $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function timezone(Closure|string|null $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function minDate(Closure|DateTimeInterface|string|null $date): static
    {
        $this->minDate = is_string($date) ? Date::parse($date) : $date;

        return $this->after($this->getMinDate(...));
    }

    public function maxDate(Closure|DateTimeInterface|string|null $date): static
    {
        $this->maxDate = is_string($date) ? Date::parse($date) : $date;

        return $this->before($this->getMaxDate(...));
    }

    public function autoclose(Closure|bool $value): self
    {
        $this->autoclose = $value;

        return $this;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getDateFormat(): string
    {
        return $this->evaluate($this->dateFormat) ?? 'Y-m-d';
    }

    public function getTimeFormat(): string
    {
        return $this->evaluate($this->timeFormat) ?? 'H:i';
    }

    public function getFullFormat(): string
    {
        return sprintf('%s %s', $this->getDateFormat(), $this->getTimeFormat());
    }

    public function getMaskFormat(): string
    {
        return match ($this->mode) {
            self::MODE_DATE, self::MODE_RANGE => $this->getDateFormat(),
            self::MODE_TIME => $this->getTimeFormat(),
            self::MODE_DATETIME => $this->getFullFormat(),
            default => throw new \LogicException(sprintf('Unsupported date picker mode [%s].', $this->mode)),
        };
    }

    public function getMomentDateFormat(): string
    {
        return $this->convertFormat($this->getDateFormat());
    }

    public function getMomentTimeFormat(): string
    {
        return $this->convertFormat($this->getTimeFormat());
    }

    public function getMomentFullFormat(): string
    {
        return sprintf('%s %s', $this->getMomentDateFormat(), $this->getMomentTimeFormat());
    }

    public function getMomentMaskFormat(): string
    {
        return match ($this->mode) {
            self::MODE_DATE, self::MODE_RANGE => $this->getMomentDateFormat(),
            self::MODE_TIME => $this->getMomentTimeFormat(),
            self::MODE_DATETIME => $this->getMomentFullFormat(),
            default => throw new \LogicException(sprintf('Unsupported date picker mode [%s].', $this->mode)),
        };
    }

    public function getLocale(): string
    {
        return $this->evaluate($this->locale) ?? config('app.locale');
    }

    public function getTimezone(): string
    {
        return $this->evaluate($this->timezone) ?? config('app.timezone');
    }

    public function getMinDate(): ?string
    {
        return $this->evaluate($this->minDate)?->format($this->getFullFormat());
    }

    public function getMaxDate(): ?string
    {
        return $this->evaluate($this->maxDate)?->format($this->getFullFormat());
    }

    public function getAutoclose(): bool
    {
        return $this->evaluate($this->autoclose);
    }

    public function table(): self
    {
        return $this->prefixIcon(null);
    }

    private function convertFormat(string $format): string
    {
        return new Converter($format)->to(self::JS_FORMAT);
    }
}
