<?php

namespace PHPinnacle\Tempo\Filters;

use Illuminate\Database\Eloquent\Builder;
use PHPinnacle\Tempo\Forms\TimePicker;

class TimeRangeFilter extends RangeFilter
{
    public function setUp(): void
    {
        parent::setUp();

        $this
            ->schema([
                TimePicker::make('from')
                    ->label(fn () => __('phpinnacle-tempo::tables.filters.bounds.from', [
                        'label' => $this->getLabel(),
                    ])),
                TimePicker::make('to')
                    ->label(fn () => __('phpinnacle-tempo::tables.filters.bounds.to', ['label' => $this->getLabel()])),
            ])
            ->query(function (Builder $query, array $data) {
                $from = $data['from'] ?? null;
                $to = $data['to'] ?? null;
                $column = $this->getColumn();

                if ($from !== null && $from !== '') {
                    $query->whereTime($column, '>=', $from);
                }

                if ($to !== null && $to !== '') {
                    $query->whereTime($column, '<=', $to);
                }
            });
    }
}
