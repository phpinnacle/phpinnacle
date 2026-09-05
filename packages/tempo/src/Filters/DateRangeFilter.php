<?php

namespace PHPinnacle\Tempo\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use PHPinnacle\Tempo\Forms\DateRangePicker;

class DateRangeFilter extends RangeFilter
{
    public static function createdAt(): self
    {
        return self::make('created_at')
            ->label(__('phpinnacle-tempo::tables.filters.created_at.label'));
    }

    public static function updatedAt(): self
    {
        return self::make('updated_at')
            ->label(__('phpinnacle-tempo::tables.filters.updated_at.label'));
    }

    public function setUp(): void
    {
        parent::setUp();

        $picker = DateRangePicker::make('range')
            ->label($this->getLabel(...));

        $this
            ->schema([$picker])
            ->query(function (Builder $query, array $data) use ($picker) {
                [$from, $to] = $this->bounds($data['range'] ?? null, $picker);
                $column = $this->getColumn();

                if ($from !== null) {
                    $query->where($column, '>=', $from);
                }

                if ($to !== null) {
                    $query->where($column, '<', $to);
                }
            });
    }

    /** @return array{?string, ?string} */
    private function bounds(mixed $range, DateRangePicker $picker): array
    {
        if (blank($range)) {
            return [null, null];
        }

        if (is_string($range)) {
            $range = explode($picker->getSeparator(), $range, 2);
        }

        if (!is_array($range)) {
            throw new InvalidArgumentException('Date range filter value is invalid.');
        }

        $format = $picker->getDateFormat();
        $from = $range[0] ?? null;
        $to = $range[1] ?? null;

        return [
            is_string($from) && $from !== ''
                ? Date::createFromFormat($format, trim($from))?->toDateString()
                : null,
            is_string($to) && $to !== ''
                ? Date::createFromFormat($format, trim($to))?->addDay()->toDateString()
                : null,
        ];
    }
}
