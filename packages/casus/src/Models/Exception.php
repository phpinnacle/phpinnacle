<?php

namespace PHPinnacle\Casus\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Phiki\Grammar\Grammar;
use PHPinnacle\Casus\Enums\ExceptionStatus;
use PHPinnacle\Casus\Enums\HttpMethod;
use PHPinnacle\Casus\Services\ExceptionReporter;
use Throwable;

/**
 * @property string $id
 * @property string $sapi
 * @property string $type
 * @property string $code
 * @property string $file
 * @property int $line
 * @property string $message
 * @property string $trace
 * @property string|null $fingerprint
 * @property int $occurrences
 * @property array<array-key, mixed> $context
 * @property HttpMethod|null $method
 * @property string|null $path
 * @property array<array-key, mixed>|null $query
 * @property array<array-key, mixed>|null $cookies
 * @property array<string, list<string>|string>|null $headers
 * @property string|null $body
 * @property string|null $ip
 * @property ExceptionStatus $status
 * @property string|null $note
 * @property CarbonImmutable $occurred_at
 */
class Exception extends Model
{
    use HasUuids;
    use MassPrunable;

    private const array GRAMMAR = [
        'application/json' => Grammar::Json,
        'application/geo+json' => Grammar::Json,
        'application/jsonc' => Grammar::Jsonc,
        'application/jsonl' => Grammar::Jsonl,
        'application/json5' => Grammar::Json5,
        'application/xml' => Grammar::Xml,
    ];

    public $timestamps = false;

    protected $table = 'exceptions';

    protected $fillable = [
        'status',
        'note',
    ];

    protected $casts = [
        'status' => ExceptionStatus::class,
        'method' => HttpMethod::class,
        'occurrences' => 'integer',
        'context' => 'array',
        'query' => 'array',
        'cookies' => 'array',
        'headers' => 'array',
        'occurred_at' => 'immutable_datetime',
    ];

    public static function setup(Exceptions $exceptions): void
    {
        $exceptions->report(fn (Throwable $e) => self::report($e, !app()->runningInConsole() ? request() : null));
    }

    public static function report(Throwable $error, ?Request $request = null): void
    {
        app(ExceptionReporter::class)->report($error, $request);
    }

    public function link(): string
    {
        return sprintf('%s:%s', $this->file, $this->line);
    }

    public function bodyGrammar(): ?Grammar
    {
        $headers = match ($this->method) {
            HttpMethod::Get => $this->headers['accept'] ?? [],
            HttpMethod::Post, HttpMethod::Put, HttpMethod::Patch, HttpMethod::Delete => $this->headers['content-type']
                ?? [],
            default => [],
        };

        if (is_string($headers)) {
            return null;
        }

        foreach ($headers as $header) {
            if (array_key_exists($header, self::GRAMMAR)) {
                return self::GRAMMAR[$header];
            }
        }

        return null;
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        $days = Config::integer('phpinnacle-casus.prune', 30);

        return static::query()->where('occurred_at', '<=', now()->subDays($days));
    }

    public function getConnectionName(): ?string
    {
        $default = parent::getConnectionName();

        return config('phpinnacle-casus.connection', $default) === null
            ? null
            : Config::string('phpinnacle-casus.connection', $default);
    }
}
