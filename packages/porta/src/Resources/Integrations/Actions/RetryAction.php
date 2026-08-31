<?php

namespace PHPinnacle\Porta\Resources\Integrations\Actions;

use Filament\Actions\Action;
use PHPinnacle\Porta\Models\Webhook;

class RetryAction extends Action
{
    public function setUp(): void
    {
        $this
            ->name('retry')
            ->hiddenLabel()
            ->label(__('phpinnacle-porta::resources.webhook.actions.retry'))
            ->icon('phosphor-repeat')
            ->color('warning')
            ->action(fn (Webhook $record) => $record->retry())
            ->visible(fn (Webhook $record) => $record->canRetry());
    }
}
