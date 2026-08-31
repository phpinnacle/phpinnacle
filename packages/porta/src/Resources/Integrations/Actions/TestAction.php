<?php

namespace PHPinnacle\Porta\Resources\Integrations\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\CodeEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Phiki\Grammar\Grammar;
use PHPinnacle\Porta\Models\Integration;
use PHPinnacle\Porta\Models\Webhook;

class TestAction
{
    public static function make(): Action
    {
        $cb = function (Set $set, ?string $state) {
            if ($state === null) {
                return;
            }

            $set('source', Webhook::get($state)->payload);
        };

        return Action::make('test')
            ->label(__('phpinnacle-porta::resources.integration.actions.test'))
            ->icon('phosphor-play-circle')
            ->fillForm(fn (Integration $record) => [
                'webhook' => $record->webhooks->first()?->getKey(),
            ])
            ->schema([
                Select::make('webhook')
                    ->options(fn (Integration $record) => $record->webhooks->pluck('id', 'id'))
                    ->afterStateHydrated($cb)
                    ->afterStateUpdated($cb)
                    ->live(),
                Group::make()
                    ->columns()
                    ->schema([
                        Textarea::make('source')
                            ->json()
                            ->required()
                            ->rows(30),
                        CodeEntry::make('target')
                            ->grammar(Grammar::Json)
                            ->copyable(),
                    ]),
            ])
            ->action(function (Integration $record, Action $action, Schema $schema, array $data) {
                $source = $data['source'] ?? '';

                $schema->state([
                    'webhook' => $data['webhook'] ?? null,
                    'source' => $source,
                    'target' => $record->handle($source),
                ]);

                $action->halt();
            })
            ->modalWidth(Width::ScreenExtraLarge)
            ->modalAutofocus(false)
            ->modalCancelAction(false);
    }
}
