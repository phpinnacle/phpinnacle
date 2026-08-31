<?php

namespace PHPinnacle\Common\Tables;

use Filament\Tables\Columns\TextColumn;

class UpdatedColumn extends TextColumn
{
    public static function getDefaultName(): string
    {
        return 'updated_at';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-common::tables.columns.updated_at.label'))
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
