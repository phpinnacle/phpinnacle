<?php

namespace PHPinnacle\Tempo\Enums;

use Filament\Support\Contracts\HasLabel;

enum CalendarView: string implements HasLabel
{
    case Month = 'dayGridMonth';
    case Week = 'timeGridWeek';
    case Day = 'timeGridDay';
    case List = 'listWeek';

    /** @return list<string> */
    public static function defaults(): array
    {
        return [
            self::Month->value,
            self::Week->value,
            self::List->value,
        ];
    }

    /**
     * @param  array<int, self|string>|null  $views
     * @return array<string, string>
     */
    public static function options(?array $views = null): array
    {
        $values = $views === null
            ? array_map(fn (self $view) => $view->value, self::cases())
            : self::values($views);
        $options = [];

        foreach ($values as $value) {
            $view = self::tryFrom($value);

            if ($view !== null) {
                $options[$view->value] = $view->getLabel();
            }
        }

        return $options;
    }

    /** @return list<string> */
    public static function values(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $normalized = [];

        foreach ($values as $value) {
            $view = $value instanceof self
                ? $value
                : (is_string($value) ? self::tryFrom($value) : null);

            if ($view !== null) {
                $normalized[$view->value] = $view->value;
            }
        }

        return array_values($normalized);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Month => __('phpinnacle-tempo::calendar.views.month'),
            self::Week => __('phpinnacle-tempo::calendar.views.week'),
            self::Day => __('phpinnacle-tempo::calendar.views.day'),
            self::List => __('phpinnacle-tempo::calendar.views.list'),
        };
    }
}
