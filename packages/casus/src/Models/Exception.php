<?php

namespace PHPinnacle\Casus\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
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
 * @property array $context
 * @property HttpMethod|null $method
 * @property string|null $path
 * @property array|null $query
 * @property array|null $cookies
 * @property array|null $headers
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

    public static function report(Throwable $error, ?Request $request = null): void
    {
        app(ExceptionReporter::class)->report($error, $request);
    }

    public static function setup(Exceptions $exceptions): void
    {
        $exceptions->report(fn (Throwable $e) => self::report($e, !app()->runningInConsole() ? request() : null));
    }

    public function bodyGrammar(): ?Grammar
    {
        $headers = match ($this->method) {
            HttpMethod::Get => $this->headers['accept'] ?? [],
            HttpMethod::Post, HttpMethod::Put, HttpMethod::Patch, HttpMethod::Delete => $this->headers['content-type']
                ?? [],
            default => [],
        };

        foreach ($headers as $header) {
            if (isset(self::GRAMMAR[$header])) {
                return self::GRAMMAR[$header];
            }
        }

        return null;
    }

    public function getConnectionName(): ?string
    {
        return config('phpinnacle-casus.connection', parent::getConnectionName());
    }

    public function link(): string
    {
        return sprintf('%s:%s', $this->file, $this->line);
    }

    public function prunable(): Builder
    {
        $days = config('phpinnacle-casus.prune', 30);

        return static::query()->where('occurred_at', '<=', now()->subDays($days));
    }
}
