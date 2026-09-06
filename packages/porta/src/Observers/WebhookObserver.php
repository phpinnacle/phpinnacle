<?php

namespace PHPinnacle\Porta\Observers;

use PHPinnacle\Porta\Jobs\ProcessWebhook;
use PHPinnacle\Porta\Models\Webhook;

class WebhookObserver
{
    public function created(Webhook $webhook): void
    {
        ProcessWebhook::dispatch($webhook)->afterCommit();
    }
}
