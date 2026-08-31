<?php

namespace PHPinnacle\Tags\Forms;

use Filament\Forms\Components\TagsInput as BaseTagsInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use PHPinnacle\Tags\Models\Tag;

class TagsInput extends BaseTagsInput
{
    public function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-tags::fields.input'))
            ->prefixIcon('phosphor-tag')
            ->disabled(fn () => Gate::denies('modify', Tag::class))
            ->saveRelationshipsWhenDisabled(false)
            ->saveRelationshipsUsing(function (Model $record, ?array $state) {
                Tag::manage($record, (array) $state);
            })
            ->loadStateFromRelationshipsUsing(function (self $component, Model $record) {
                $component->state(Tag::retrieve($record));
            });
    }
}
