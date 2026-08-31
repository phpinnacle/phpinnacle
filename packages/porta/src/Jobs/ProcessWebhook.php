<?php

namespace PHPinnacle\Porta\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Support\Facades\DB;
use PHPinnacle\Porta\Models\Webhook;
use PHPinnacle\Porta\Services\WebhookRegistry;
use Throwable;

#[DeleteWhenMissingModels]
class ProcessWebhook implements ShouldQueue
{
    use Queueable;

    public function __construct(
        #[WithoutRelations]
        private Webhook $webhook,
    ) {}

    public function failed(?Throwable $exception): void
    {
        $this->webhook->fail($exception);
    }

    public function handle(WebhookRegistry $handlers): void
    {
        DB::transaction(function () use ($handlers) {
            [$type, $payload] = $this->webhook->progress();

            $handler = $handlers->get($type);

            if (!$handler) {
                throw new \RuntimeException("No handler registered for webhook type: {$type}");
            }

            $handler->process($payload);

            $this->webhook->complete();
        });
    }
}
