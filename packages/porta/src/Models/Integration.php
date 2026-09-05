<?php

namespace PHPinnacle\Porta\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PHPinnacle\Porta\Enums\IntegrationAuth;
use PHPinnacle\Porta\Enums\IntegrationFormat;
use PHPinnacle\Porta\Enums\IntegrationResponse;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $type
 * @property string $title
 * @property IntegrationFormat $format
 * @property IntegrationAuth $auth
 * @property string|null $auth_key
 * @property string|null $auth_secret
 * @property IntegrationResponse $response_kind
 * @property int $response_code
 * @property array $transforms
 * @property bool $is_active
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Collection<Webhook> $webhooks
 */
class Integration extends Model
{
    use HasUuids;

    protected $table = 'integrations';

    protected $attributes = [
        'format' => IntegrationFormat::JSON->value,
        'auth' => IntegrationAuth::None->value,
        'response_kind' => IntegrationResponse::Empty->value,
        'response_code' => 200,
        'transforms' => '[]',
        'is_active' => true,
    ];

    protected $casts = [
        'format' => IntegrationFormat::class,
        'auth' => IntegrationAuth::class,
        'response_kind' => IntegrationResponse::class,
        'transforms' => 'array',
    ];

    protected $fillable = [
        'type',
        'title',
        'format',
        'auth',
        'auth_key',
        'auth_secret',
        'response_kind',
        'response_code',
        'transforms',
        'is_active',
    ];

    public static function active(): Builder
    {
        return self::query()->where('is_active', true);
    }

    public static function get(string $value, string $key = 'id'): self
    {
        return self::active()->where($key, $value)->firstOrFail();
    }

    public function authorize(Request $request): void
    {
        if ($this->auth === IntegrationAuth::None) {
            return;
        }

        $secret = match ($this->auth) {
            IntegrationAuth::Header => $request->header($this->auth_key),
            IntegrationAuth::Query => $request->query($this->auth_key),
            default => null,
        };

        abort_unless(
            is_string($secret) && is_string($this->auth_secret) && hash_equals($this->auth_secret, $secret),
            403,
        );
    }

    public function handle(string $payload): array
    {
        $payload = $this->format->decode($payload);
        $transforms = array_map(Transform::make(...), $this->transforms);

        foreach ($transforms as $transform) {
            $payload = $transform->apply($payload);
        }

        return $payload;
    }

    public function schedule(string $origin, string $payload, array $headers): Webhook
    {
        return Webhook::schedule($this, $origin, $payload, $headers);
    }

    public function toggleActive(): void
    {
        $this->is_active = !$this->is_active;
        $this->save();
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }
}
