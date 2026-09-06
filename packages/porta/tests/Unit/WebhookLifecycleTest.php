<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPinnacle\Porta\Enums\WebhookStatus;
use PHPinnacle\Porta\Handler;
use PHPinnacle\Porta\Jobs\ProcessWebhook;
use PHPinnacle\Porta\Models\Integration;
use PHPinnacle\Porta\Models\Webhook;
use PHPinnacle\Porta\Services\WebhookRegistry;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    (require __DIR__ . '/../../database/migrations/create_porta_tables.php')->up();
    config()->set('queue.default', 'sync');
    config()->set('queue.connections.sync.after_commit', false);

    $this->payloads = [];
    app(WebhookRegistry::class)->register(new Handler('test', 'Test', function (array $payload) {
        $this->payloads[] = $payload;
    }));
    $this->integration = Integration::query()->create(['title' => 'Test', 'type' => 'test']);
});

function scheduled_webhook(Integration $integration): Webhook
{
    return Webhook::schedule($integration, 'https://example.com', '{"name":" Alice "}', []);
}

it('processes new webhooks only after the outer transaction commits', function () {
    DB::beginTransaction();
    DB::beginTransaction();
    $webhook = scheduled_webhook($this->integration);
    $webhook->save();

    expect($this->payloads)->toBe([]);
    DB::commit();
    expect($this->payloads)->toBe([]);
    DB::commit();

    expect($this->payloads)
        ->toBe([['name' => ' Alice ']])
        ->and($webhook->refresh()->status)
        ->toBe(WebhookStatus::Completed)
        ->and($webhook->processed_at)
        ->not->toBeNull();
});

it('does not process rolled back webhook creation', function () {
    DB::beginTransaction();
    $webhook = scheduled_webhook($this->integration);
    $webhook->save();
    DB::rollBack();

    expect($this->payloads)->toBe([])->and(Webhook::query()->count())->toBe(0);
});

it('processes transformed payloads and clears previous failure metadata', function () {
    Queue::fake();
    $this->integration->update(['transforms' => [['type' => 'trim', 'path' => 'name']]]);
    $webhook = scheduled_webhook($this->integration);
    $webhook->save();
    $webhook->fail(new RuntimeException('Previous attempt'));
    new ProcessWebhook($webhook)->handle(app(WebhookRegistry::class));

    expect($this->payloads)
        ->toBe([['name' => 'Alice']])
        ->and($webhook->refresh()->status)
        ->toBe(WebhookStatus::Completed)
        ->and($webhook->error)
        ->toBeNull();
});

it('persists failure without exception details when the queue supplies null', function () {
    Queue::fake();
    $webhook = scheduled_webhook($this->integration);
    $webhook->save();
    new ProcessWebhook($webhook)->failed(null);

    expect($webhook->refresh()->status)
        ->toBe(WebhookStatus::Failed)
        ->and($webhook->processed_at)
        ->not
        ->toBeNull()
        ->and($webhook->error)
        ->toMatchArray([
            'message' => 'Webhook processing failed without exception details.',
            'type' => null,
            'file' => null,
            'line' => null,
        ]);
});

it('propagates processing failures and records their original details', function (bool $transform) {
    Queue::fake();
    $exception = ValidationException::withMessages(['name' => 'Rejected']);
    app(WebhookRegistry::class)->register(new Handler('test', 'Test', fn () => throw $exception));
    $webhook = scheduled_webhook($this->integration);
    if ($transform) {
        $webhook->payload = 'invalid json';
    }
    $webhook->save();
    $job = new ProcessWebhook($webhook);

    try {
        $job->handle(app(WebhookRegistry::class));
        $this->fail('Processing should throw.');
    } catch (ValidationException|JsonException $error) {
        expect($webhook->refresh()->status)->toBe(WebhookStatus::Scheduled);
        $job->failed($error);
        expect($webhook->refresh()->status)
            ->toBe(WebhookStatus::Failed)
            ->and($webhook->error['message'])
            ->toBe($error->getMessage())
            ->and($webhook->error['type'])
            ->toBe($error::class);
        if (!$transform) {
            expect($webhook->error['errors'])->toBe(['name' => ['Rejected']]);
        }
    }
})->with([false, true]);

it('dispatches a retry once after commit and discards rolled back retries', function () {
    $webhook = Webhook::withoutEvents(function () {
        $webhook = scheduled_webhook($this->integration);
        $webhook->save();

        return $webhook;
    });
    $webhook->fail(new RuntimeException('Retry me'));
    DB::beginTransaction();
    $webhook->retry();
    expect($this->payloads)->toBe([]);
    DB::rollBack();
    expect($webhook->refresh()->status)->toBe(WebhookStatus::Failed)->and($this->payloads)->toBe([]);

    DB::beginTransaction();
    $webhook->retry();
    $webhook->retry();
    expect($this->payloads)->toBe([]);
    DB::commit();
    expect($this->payloads)->toHaveCount(1)->and($webhook->refresh()->status)->toBe(WebhookStatus::Completed);
});
