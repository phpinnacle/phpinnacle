<?php

namespace PHPinnacle\Tags\Tables;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Gate;
use PHPinnacle\Tags\Models\Tag;

class TagsColumn extends TextColumn
{
    public static function getDefaultName(): string
    {
        return 'tags';
    }

    public function setUp(): void
    {
        $this
            ->label(__('phpinnacle-tags::fields.column'))
            ->badge()
            ->visible(fn () => Gate::allows('view', Tag::class))
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
