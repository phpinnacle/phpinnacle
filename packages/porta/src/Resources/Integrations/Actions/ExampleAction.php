<?php

namespace PHPinnacle\Porta\Resources\Integrations\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use PHPinnacle\Porta\Resources\Integrations\Pages\ManageWebhooks;
use PHPinnacle\Porta\Services\WebhookRegistry;

class ExampleAction
{
    public static function make(): Action
    {
        return Action::make('example')
            ->action(function (Textarea $component, ManageWebhooks $livewire, WebhookRegistry $registry) {
                $handler = $registry->get($livewire->record->type);

                $component->state($handler?->getExample());
            });
    }
}
