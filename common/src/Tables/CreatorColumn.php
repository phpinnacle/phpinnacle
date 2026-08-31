<?php

namespace PHPinnacle\Common\Tables;

use Filament\Models\Contracts\HasName;
use Filament\Support\Contracts\HasLabel;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class CreatorColumn extends TextColumn
{
    public static function getDefaultName(): string
    {
        return 'creator';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-common::tables.columns.created_by.label'))
            ->formatStateUsing($this->format(...))
            ->toggleable(isToggledHiddenByDefault: true);
    }

    private function format(?Model $state): ?string
    {
        return match (true) {
            $state instanceof HasName => $state->getFilamentName(),
            $state instanceof HasLabel => $state->getLabel(),
            $state->hasAttribute('name') => $state->getAttribute('name'),
            $state->hasAttribute('email') => $state->getAttribute('email'),
            default => null,
        };
    }
}
