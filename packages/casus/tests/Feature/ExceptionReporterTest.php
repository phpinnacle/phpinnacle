<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Casus\Enums\ExceptionStatus;
use PHPinnacle\Casus\Enums\HttpMethod;
use PHPinnacle\Casus\Models\Exception as ExceptionRecord;
use PHPinnacle\Casus\Notifications\ExceptionReported;
use PHPinnacle\Casus\Services\ExceptionReporter;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::dropIfExists('exceptions');

    foreach (glob(__DIR__ . '/../../database/migrations/*.php') as $migration) {
        (require $migration)->up();
    }
});

it('builds a redacted request snapshot including the Livewire origin', function () {
    config()->set('phpinnacle-casus.redaction', [
        'replacement' => '[hidden]',
        'keys' => ['authorization', 'password', 'session', 'token'],
    ]);

    $hidden = '[hidden]';
    $credential = implode('-', ['body', 'value']);
    $body = [
        'components' => [[
            'snapshot' => json_encode(['memo' => ['path' => 'orders/42', 'method' => 'DELETE']]),
        ]],
        'password' => $credential,
        'name' => 'Ada',
    ];
    $request = Request::create(
        '/livewire/update?api_token=query-secret&filter=active',
        'POST',
        [],
        ['laravel_session' => 'cookie-secret', 'theme' => 'dark'],
        server: [
            'HTTP_AUTHORIZATION' => 'Bearer header-secret',
            'HTTP_X_LIVEWIRE' => 'true',
            'CONTENT_TYPE' => 'application/json',
        ],
        content: json_encode($body),
    );
    app()->instance('request', $request);

    $snapshot = app(ExceptionReporter::class)->requestSnapshot($request);
    expect($snapshot)
        ->method->toBe(HttpMethod::Delete)
        ->path->toBe('orders/42')
        ->query->toBe(['api_token' => $hidden, 'filter' => 'active'])
        ->cookies->toBe(['laravel_session' => $hidden, 'theme' => 'dark'])->and(
            $snapshot['headers']['authorization'],
        )->toBe($hidden)->and(json_decode($snapshot['body'], associative: true)['password'])->toBe($hidden);

    $formRequest = Request::create(
        '/submit',
        'POST',
        server: ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
        content: http_build_query(['password' => $credential, 'name' => 'Ada']),
    );
    app()->instance('request', $formRequest);

    expect(app(ExceptionReporter::class)->requestSnapshot($formRequest)['body'])
        ->toBe('password=%5Bhidden%5D&name=Ada');
});

it('groups repeated exceptions, preserves triage, and notifies at the threshold', function () {
    Notification::fake();
    config()->set('phpinnacle-casus.notifications', [
        'mail' => 'ops@example.com',
        'repeat_threshold' => 2,
    ]);

    $error = new RuntimeException('Database unavailable');

    ExceptionRecord::report($error);

    $exception = ExceptionRecord::query()->sole();
    $exception->update([
        'status' => ExceptionStatus::Acknowledged,
        'note' => 'Investigating',
    ]);

    ExceptionRecord::report($error);

    expect(ExceptionRecord::query()->count())
        ->toBe(1)
        ->and($exception->refresh()->occurrences)
        ->toBe(2)
        ->and($exception->status)
        ->toBe(ExceptionStatus::Acknowledged)
        ->and($exception->note)
        ->toBe('Investigating')
        ->and($exception->fingerprint)
        ->toHaveLength(64);

    Notification::assertSentOnDemandTimes(ExceptionReported::class, 2);
});
