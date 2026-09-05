<?php

namespace PHPinnacle\Casus\Services;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPinnacle\Casus\Enums\HttpMethod;
use PHPinnacle\Casus\Models\Exception;
use PHPinnacle\Casus\Notifications\ExceptionReported;
use PHPinnacle\Casus\Support\SensitiveValuePresenter;
use Throwable;

class ExceptionReporter
{
    private const int JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    public function __construct(
        private SensitiveValuePresenter $sensitiveValues,
    ) {}

    public function report(Throwable $error, ?Request $request = null): void
    {
        try {
            $file = ltrim(str_replace(base_path(), '', $error->getFile()), DIRECTORY_SEPARATOR);
            $fingerprint = hash('sha256', implode("\0", [
                $error::class,
                $error->getMessage(),
                $error->getCode(),
                $file,
                $error->getLine(),
            ]));
            // ponytail: best-effort read/update; use an atomic upsert if concurrent counts must be exact.
            $exception = Exception::query()->where('fingerprint', $fingerprint)->first() ?? new Exception;

            $exception->forceFill([
                'sapi' => php_sapi_name(),
                'type' => $error::class,
                'code' => $error->getCode(),
                'file' => $file,
                'line' => $error->getLine(),
                'message' => $error->getMessage(),
                'trace' => $error->getTraceAsString(),
                'context' => $this->context($error),
                'fingerprint' => $fingerprint,
                'occurrences' => $exception->exists ? $exception->occurrences + 1 : 1,
                'occurred_at' => CarbonImmutable::now(),
                ...$this->requestSnapshot($request),
            ])->saveQuietly();

            $this->notify($exception);
        } catch (Throwable) {
            return;
        }
    }

    /**
     * @return array{
     *     method: HttpMethod|null,
     *     path: string|null,
     *     query: array|null,
     *     cookies: array|null,
     *     headers: array|null,
     *     body: string|null,
     *     ip: string|null,
     * }
     */
    public function requestSnapshot(?Request $request): array
    {
        if ($request === null) {
            return [
                'method' => null,
                'path' => null,
                'query' => null,
                'cookies' => null,
                'headers' => null,
                'body' => null,
                'ip' => null,
            ];
        }

        if (Livewire::isLivewireRequest()) {
            $method = Livewire::originalMethod();
            $path = Livewire::originalPath();
        } else {
            $method = $request->getMethod();
            $path = $request->path();
        }

        return [
            'method' => HttpMethod::tryFrom($method),
            'path' => $path,
            'query' => $this->sensitiveValues->redact($request->query()),
            'cookies' => $this->sensitiveValues->redact($request->cookies->all()),
            'headers' => $this->sensitiveValues->redact(Arr::except($request->headers->all(), 'cookie')),
            'body' => $this->redactBody($request),
            'ip' => $request->ip(),
        ];
    }

    private function context(Throwable $error): array
    {
        if (!method_exists($error, 'context')) {
            return [];
        }

        try {
            /** @var mixed $context */
            $context = $error->context();

            if (is_object($context) || is_array($context)) {
                return json_decode(json_encode($context, JSON_THROW_ON_ERROR), true);
            }
        } catch (Throwable) {
            return [];
        }

        return [];
    }

    private function notify(Exception $exception): void
    {
        $mail = config('phpinnacle-casus.notifications.mail');
        $threshold = config('phpinnacle-casus.notifications.repeat_threshold');

        if ($mail === null || !$exception->wasRecentlyCreated && $exception->occurrences !== $threshold) {
            return;
        }

        Notification::route('mail', $mail)->notify(new ExceptionReported($exception));
    }

    private function redactBody(Request $request): string
    {
        $body = $request->getContent();

        if ($body === '') {
            return '';
        }

        $data = json_decode($body, true);

        if (is_array($data)) {
            return (string) json_encode($this->sensitiveValues->redact($data), self::JSON_FLAGS);
        }

        $contentType = (string) $request->headers->get('content-type');

        if (str_starts_with($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($body, $data);

            return http_build_query($this->sensitiveValues->redact($data));
        }

        if (str_starts_with($contentType, 'multipart/form-data')) {
            return (string) json_encode(
                $this->sensitiveValues->redact($request->request->all()),
                self::JSON_FLAGS,
            );
        }

        return $body;
    }
}
