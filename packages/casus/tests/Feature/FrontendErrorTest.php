<?php

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Casus\Enums\ExceptionStatus;
use PHPinnacle\Casus\Models\Exception as ExceptionRecord;
use PHPinnacle\Casus\Notifications\ExceptionReported;
use Tests\TestCase;

class FrontendErrorTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('phpinnacle-casus.frontend.enabled', true);
        $app['config']->set('cache.default', 'array');
    }
}

uses(FrontendErrorTestCase::class);

beforeEach(function () {
    Schema::dropIfExists('exceptions');

    foreach (glob(__DIR__ . '/../../database/migrations/*.php') as $migration) {
        (require $migration)->up();
    }
});

it('records a guest browser error with its original trace and redacted request metadata', function () {
    $this->postJson(
        route('phpinnacle-casus.frontend.report'),
        [
            'type' => 'TypeError',
            'message' => 'Cannot read properties of undefined',
            'file' => 'https://example.com/assets/app.js',
            'line' => 12,
            'column' => 7,
            'trace' => "TypeError: Cannot read properties of undefined\n    at https://example.com/assets/app.js:12:7",
            'path' => '/orders/42',
            'ignored' => 'unvalidated data',
        ],
        ['Authorization' => 'Bearer private-value'],
    )->assertNoContent();

    $exception = ExceptionRecord::query()->sole();

    expect($exception->sapi)
        ->toBe('browser')
        ->and($exception->type)
        ->toBe('TypeError')
        ->and($exception->message)
        ->toBe('Cannot read properties of undefined')
        ->and($exception->file)
        ->toBe('https://example.com/assets/app.js')
        ->and($exception->line)
        ->toBe(12)
        ->and($exception->context)
        ->toBe(['column' => 7])
        ->and($exception->trace)
        ->toContain('app.js:12:7')
        ->and($exception->path)
        ->toBe('/orders/42')
        ->and($exception->headers['authorization'])
        ->toBe('[hidden]')
        ->and($exception->body)
        ->toBeNull()
        ->and($exception->occurrences)
        ->toBe(1)
        ->and($exception->status)
        ->toBe(ExceptionStatus::Open);
});

it('accepts an authenticated user and errors without optional browser metadata', function () {
    $this->actingAs(new GenericUser(['id' => 42]));

    $this->postJson('/casus/errors', ['type' => 'Error', 'message' => 'Unhandled rejection'])
        ->assertNoContent();

    $exception = ExceptionRecord::query()->sole();

    expect($exception->file)
        ->toBe('')
        ->and($exception->line)
        ->toBe(0)
        ->and($exception->context)
        ->toBe(['column' => 0])
        ->and($exception->trace)
        ->toBe('')
        ->and($exception->path)
        ->toBe('casus/errors');
});

it('groups browser errors across pages while preserving triage and notification thresholds', function () {
    Notification::fake();
    config()->set('phpinnacle-casus.notifications', [
        'mail' => 'ops@example.com',
        'repeat_threshold' => 2,
    ]);
    $payload = ['type' => 'Error', 'message' => 'Failed', 'file' => '/app.js', 'line' => 3, 'column' => 9];

    $this->postJson('/casus/errors', [...$payload, 'path' => '/first'])->assertNoContent();
    $exception = ExceptionRecord::query()->sole();
    $exception->update(['status' => ExceptionStatus::Resolved, 'note' => 'Fixed']);

    $this->postJson('/casus/errors', [...$payload, 'path' => '/second'])->assertNoContent();

    expect(ExceptionRecord::query()->count())
        ->toBe(1)
        ->and($exception->refresh()->occurrences)
        ->toBe(2)
        ->and($exception->status)
        ->toBe(ExceptionStatus::Resolved)
        ->and($exception->note)
        ->toBe('Fixed')
        ->and($exception->path)
        ->toBe('/second');

    Notification::assertSentOnDemandTimes(ExceptionReported::class, 2);

    $this->postJson('/casus/errors', [...$payload, 'column' => 10])->assertNoContent();

    expect(ExceptionRecord::query()->count())->toBe(2);
});

it('rejects invalid browser input without creating an exception', function (string $field, mixed $value) {
    $this
        ->postJson('/casus/errors', [
            ...['type' => 'Error', 'message' => 'Failed'],
            $field => $value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);

    expect(ExceptionRecord::query()->count())->toBe(0);
})->with([
    'missing type' => ['type', null],
    'empty message' => ['message', ''],
    'non-string message' => ['message', ['invalid']],
    'long type' => ['type', str_repeat('x', 256)],
    'long message' => ['message', str_repeat('x', 16_001)],
    'long file' => ['file', str_repeat('x', 256)],
    'negative line' => ['line', -1],
    'overflow line' => ['line', 2_147_483_648],
    'fractional column' => ['column', 1.5],
    'long trace' => ['trace', str_repeat('x', 16_001)],
    'absolute page URL' => ['path', 'https://example.com/orders'],
    'page query' => ['path', '/orders?token=private'],
    'page fragment' => ['path', '/orders#private'],
    'long page path' => ['path', '/' . str_repeat('x', 255)],
]);

it('requires CSRF protection outside the test environment', function () {
    app()->instance('env', 'local');

    $this->postJson('/casus/errors', ['type' => 'Error', 'message' => 'Failed'])->assertStatus(419);

    expect(ExceptionRecord::query()->count())->toBe(0);

    $csrf = 'frontend-test-csrf';
    $this
        ->withSession(['_token' => $csrf])
        ->postJson('/casus/errors', ['type' => 'Error', 'message' => 'Failed'], ['X-CSRF-TOKEN' => $csrf])
        ->assertNoContent();

    expect(ExceptionRecord::query()->count())->toBe(1);
});

it('limits default ingestion bursts per IP without blocking a different visitor', function () {
    $this->freezeTime();
    $payload = ['type' => 'Error', 'message' => 'Repeated failure'];

    for ($attempt = 0; $attempt < 3; $attempt++) {
        $this->postJson('/casus/errors', $payload)->assertNoContent();
    }

    $this->postJson('/casus/errors', $payload)->assertStatus(429)->assertHeader('Retry-After', '1');

    expect(ExceptionRecord::query()->sole()->occurrences)->toBe(3);

    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.42'])->postJson('/casus/errors', $payload)->assertNoContent();
});

it('surfaces persistence failures instead of acknowledging an unsaved error', function () {
    Schema::drop('exceptions');

    $this->postJson('/casus/errors', ['type' => 'Error', 'message' => 'Failed'])->assertStatus(500);
});

it('applies a configurable global budget across IP addresses and resumes after its window', function (int $seconds) {
    $this->freezeTime();
    config()->set('phpinnacle-casus.frontend.rate_limits', [
        'global' => [$seconds => 2],
        'per_ip' => [$seconds => 10],
    ]);
    $payload = ['type' => 'Error', 'message' => 'Failed'];

    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1'])->postJson('/casus/errors', $payload)->assertNoContent();
    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.2'])->postJson('/casus/errors', $payload)->assertNoContent();
    $this
        ->withServerVariables(['REMOTE_ADDR' => '192.0.2.3'])
        ->postJson('/casus/errors', $payload)
        ->assertStatus(429)
        ->assertHeader('Retry-After', (string) $seconds);

    expect(ExceptionRecord::query()->sole()->occurrences)->toBe(2);

    $this->travel($seconds + 1)->seconds();
    $this->postJson('/casus/errors', $payload)->assertNoContent();

    expect(ExceptionRecord::query()->sole()->occurrences)->toBe(3);
})->with([1, 10, 60, 7]);

it('applies the configured IP limit to guests and users without spending the remaining global budget', function () {
    $this->freezeTime();
    config()->set('phpinnacle-casus.frontend.rate_limits', ['global' => [60 => 2], 'per_ip' => [60 => 1]]);
    $payload = ['type' => 'Error', 'message' => 'Failed'];

    $this->postJson('/casus/errors', $payload)->assertNoContent();
    $this->actingAs(new GenericUser(['id' => 42]));
    $this->postJson('/casus/errors', $payload)->assertStatus(429)->assertHeader('Retry-After', '60');

    expect(ExceptionRecord::query()->sole()->occurrences)->toBe(1);

    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.42'])->postJson('/casus/errors', $payload)->assertNoContent();

    expect(ExceptionRecord::query()->sole()->occurrences)->toBe(2);
});

it('counts invalid payloads toward the ingestion budget', function () {
    config()->set('phpinnacle-casus.frontend.rate_limits', ['global' => [60 => 1], 'per_ip' => [60 => 10]]);

    $this->postJson('/casus/errors', [])->assertUnprocessable();
    $this->postJson('/casus/errors', ['type' => 'Error', 'message' => 'Failed'])->assertStatus(429);

    expect(ExceptionRecord::query()->count())->toBe(0);
});

it('enforces independent second, ten-second, and minute buckets', function (string $scope) {
    $this->freezeTime();
    config()->set('phpinnacle-casus.frontend.rate_limits', [
        'global' => [1 => 100, 10 => 100, 60 => 100],
        'per_ip' => [1 => 100, 10 => 100, 60 => 100],
    ]);
    config()->set('phpinnacle-casus.frontend.rate_limits.' . $scope, [1 => 2, 10 => 3, 60 => 4]);
    $payload = ['type' => 'Error', 'message' => 'Repeated failure'];
    $send = function (int $visitor) use ($scope, $payload) {
        $ip = $scope === 'global' ? '192.0.2.' . $visitor : '192.0.2.1';

        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->postJson('/casus/errors', $payload);
    };

    $send(1)->assertNoContent();
    $send(2)->assertNoContent();
    $send(3)->assertStatus(429)->assertHeader('Retry-After', '1');
    expect(ExceptionRecord::query()->sole()->occurrences)->toBe(2);

    $this->travel(1)->seconds();
    $send(3)->assertNoContent();
    $send(4)->assertStatus(429)->assertHeader('Retry-After', '9');
    expect(ExceptionRecord::query()->sole()->occurrences)->toBe(3);

    $this->travel(9)->seconds();
    $send(4)->assertNoContent();
    expect(ExceptionRecord::query()->sole()->occurrences)->toBe(4);

    $this->travel(1)->seconds();
    $send(5)->assertStatus(429)->assertHeader('Retry-After', '49');
    expect(ExceptionRecord::query()->sole()->occurrences)->toBe(4);

    $this->travel(49)->seconds();
    $send(5)->assertNoContent();
    expect(ExceptionRecord::query()->sole()->occurrences)->toBe(5);
})->with(['global', 'per_ip']);
