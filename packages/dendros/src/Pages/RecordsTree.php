<?php

namespace PHPinnacle\Dendros\Pages;

use Filament\Resources\Pages\ListRecords;
use Kisame76\FilamentTreeTable\Concerns\InteractsWithExpandableRows;
use Kisame76\FilamentTreeTable\Contracts\HasExpandableRows;

abstract class RecordsTree extends ListRecords implements HasExpandableRows
{
    use InteractsWithExpandableRows;
}
