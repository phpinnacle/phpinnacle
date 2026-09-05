<?php

namespace PHPinnacle\Porta\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPinnacle\Porta\Enums\WebhookStatus;
use PHPinnacle\Porta\Jobs\ProcessWebhook;
use PHPinnacle\Porta\Observers\WebhookObserver;
use stdClass;
use Throwable;

/**
 * @property string $id
 * @property string $integration_id
 * @property string $origin
 * @property string $payload
 * @property array $headers
 * @property array|null $error
 * @property WebhookStatus $status
 * @property CarbonImmutable|null $processed_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Integration $integration
 */
#[ObservedBy(WebhookObserver::class)]
class Webhook extends Model
{
    use HasUuids;

    protected $table = 'webhooks';

    protected $attributes = [
        'status' => WebhookStatus::Scheduled,
    ];

    protected $casts = [
        'headers' => 'array',
        'error' => 'array',
        'processed_at' => 'immutable_datetime',
        'status' => WebhookStatus::class,
    ];

    protected $fillable = [
        'integration_id',
        'origin',
        'payload',
        'headers',
        'error',
        'status',
        'processed_at',
    ];

    public static function get(string $id): self
    {
        return self::query()->findOrFail($id);
    }

    public static function schedule(Integration $integration, string $origin, string $payload, array $headers): self
    {
        $self = new self;
        $self->integration_id = $integration->id;
        $self->origin = $origin;
        $self->payload = $payload;
        $self->headers = array_map(fn (mixed $value) => is_array($value) ? implode(';', $value) : $value, $headers);
        $self->status = WebhookStatus::Scheduled;

        return $self;
    }

    public function canRetry(): bool
    {
        return $this->status === WebhookStatus::Failed;
    }

    public function complete(): void
    {
        $this->status = WebhookStatus::Completed;
        $this->error = null;
        $this->processed_at = CarbonImmutable::now();
        $this->save();
    }

    public function fail(?Throwable $error): void
    {
        $info = [
            'message' => $error->getMessage(),
            'errors' => $error instanceof ValidationException ? $error->errors() : new stdClass,
            'type' => get_class($error),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
        ];

        $this->status = WebhookStatus::Failed;
        $this->error = $info;
        $this->processed_at = CarbonImmutable::now();
        $this->save();
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function progress(): array
    {
        $this->status = WebhookStatus::Progress;
        $this->processed_at = CarbonImmutable::now();
        $this->save();

        return [
            $this->integration->type,
            $this->integration->handle($this->payload),
        ];
    }

    public function render(): array
    {
        return [
            'id' => $this->id,
            'origin' => $this->origin,
            'status' => $this->status,
            'processed_at' => $this->processed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function retry(): void
    {
        if ($this->status !== WebhookStatus::Failed) {
            return;
        }

        DB::transaction(function () {
            $this->status = WebhookStatus::Scheduled;
            $this->processed_at = null;
            $this->save();

            ProcessWebhook::dispatch($this)->afterCommit();
        });
    }

    protected static function booted(): void
    {
        self::saving(function (self $record) {
            $record->payload ??= '';
        });
    }
}
