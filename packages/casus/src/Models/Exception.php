<?php

namespace PHPinnacle\Casus\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Livewire\Livewire;
use Phiki\Grammar\Grammar;
use PHPinnacle\Casus\Enums\HttpMethod;
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
 * @property array $context
 * @property HttpMethod|null $method
 * @property string|null $path
 * @property array|null $query
 * @property array|null $cookies
 * @property array|null $headers
 * @property string|null $body
 * @property string|null $ip
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

    protected $casts = [
        'method' => HttpMethod::class,
        'context' => 'array',
        'query' => 'array',
        'cookies' => 'array',
        'headers' => 'array',
    ];

    public static function report(Throwable $error, ?Request $request = null): void
    {
        $self = new self;
        $self->sapi = php_sapi_name();
        $self->type = get_class($error);
        $self->code = $error->getCode();
        $self->file = ltrim(str_replace(base_path(), '', $error->getFile()), DIRECTORY_SEPARATOR);
        $self->line = $error->getLine();
        $self->message = $error->getMessage();
        $self->trace = $error->getTraceAsString();
        $self->occurred_at = CarbonImmutable::now();

        if (method_exists($error, 'context')) {
            try {
                /** @var mixed $context */
                $context = $error->context();

                if (is_object($context) || is_array($context)) {
                    $self->context = json_decode(json_encode($context, JSON_THROW_ON_ERROR), true);
                }
            } catch (Throwable) {
            }
        }

        if ($request !== null) {
            $livewire = Livewire::isLivewireRequest();

            $method = $livewire ? Livewire::originalMethod() : $request->getMethod();
            $self->method = HttpMethod::tryFrom($method);
            $self->path = $livewire ? Livewire::originalPath() : $request->path();
            $self->query = $request->query();
            $self->cookies = $request->cookies->all();
            $self->headers = Arr::except($request->headers->all(), 'cookie');
            $self->body = $request->getContent();
            $self->ip = $request->ip();
        }

        try {
            $self->saveQuietly();
        } catch (Throwable) {
            // DO NOTHING
        }
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
        $days = config('phpinnacle-casus.prune.days', 30);

        return static::query()->where('occurred_at', '<=', now()->subDays($days));
    }
}
