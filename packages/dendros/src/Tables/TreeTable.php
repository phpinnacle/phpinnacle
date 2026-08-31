<?php

namespace PHPinnacle\Dendros\Tables;

use Filament\Tables\Table;
use Kisame76\FilamentTreeTable\ExpandableRows;

class TreeTable
{
    public static function make(Table $table): Table
    {
        return ExpandableRows::make()
            ->expandAllAction(false)
            ->collapseAllAction(false)
            ->paginateByRoot()
            ->applyTo($table);
    }
}
