<?php

namespace PHPinnacle\Common\Tables;

use Filament\Tables\Columns\TextColumn;

class CreatedColumn extends TextColumn
{
    public static function getDefaultName(): string
    {
        return 'created_at';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-common::tables.columns.created_at.label'))
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
