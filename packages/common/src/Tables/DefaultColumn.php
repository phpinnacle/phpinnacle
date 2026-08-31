<?php

namespace PHPinnacle\Common\Tables;

use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class DefaultColumn extends IconColumn
{
    public static function getDefaultName(): string
    {
        return 'is_default';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-common::tables.columns.default.label'))
            ->disabledClick(fn (Model $record) => Gate::denies('update', $record))
            ->boolean()
            ->alignCenter()
            ->sortable()
            ->toggleable();
    }
}
