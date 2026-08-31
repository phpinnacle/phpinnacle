<?php

namespace PHPinnacle\Dendros\Pages;

use Filament\Resources\RelationManagers\RelationManager;
use Kisame76\FilamentTreeTable\Concerns\InteractsWithExpandableRows;
use Kisame76\FilamentTreeTable\Contracts\HasExpandableRows;

abstract class RelationsTree extends RelationManager implements HasExpandableRows
{
    use InteractsWithExpandableRows;
}
