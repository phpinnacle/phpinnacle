<?php

namespace PHPinnacle\Tags\Actions;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use PHPinnacle\Tags\Forms\TagsInput;
use PHPinnacle\Tags\Models\Tag;

class TagsBulkAction extends BulkAction
{
    public static function getDefaultName(): string
    {
        return 'tags';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->icon('phosphor-tag')
            ->label(__('phpinnacle-tags::actions.bulk.label'))
            ->visible(fn () => Gate::allows('modify', Tag::class))
            ->modalHeading(__('phpinnacle-tags::actions.bulk.heading'))
            ->modalDescription(__('phpinnacle-tags::actions.bulk.description'))
            ->modalIcon('phosphor-tag')
            ->modalCancelAction(false)
            ->modalSubmitAction(function (Action $action) {
                $action
                    ->label(__('phpinnacle-tags::actions.sync'))
                    ->icon('phosphor-check-circle')
                    ->color('primary');
            })
            ->extraModalFooterActions(fn (BulkAction $action) => [
                $action
                    ->makeModalSubmitAction('drop', arguments: ['drop' => true])
                    ->label(__('phpinnacle-tags::actions.drop'))
                    ->icon('phosphor-trash')
                    ->color('danger'),
            ])
            ->schema([
                TagsInput::make('tags'),
            ])
            ->action(function (Collection $records, array $data, array $arguments) {
                $drop = $arguments['drop'] ?? false;

                Tag::manage($records, $data['tags'] ?? [], $drop);
            });
    }
}
