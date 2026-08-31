<?php

namespace PHPinnacle\Cerber\Models;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $user_id
 * @property string $provider_id
 * @property string $external_id
 * @property string|null $email
 * @property array|null $profile
 * @property string|null $access_token
 * @property string|null $refresh_token
 * @property CarbonImmutable|null $token_expires_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read User $user
 * @property-read Provider $provider
 */
class SocialAccount extends Model
{
    use HasUuids;

    public $timestamps = true;

    protected $table = 'social_accounts';

    protected $casts = [
        'profile' => 'array',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'token_expires_at' => 'immutable_datetime',
    ];

    protected $fillable = [
        'user_id',
        'provider_id',
        'external_id',
        'email',
        'profile',
        'access_token',
        'refresh_token',
        'token_expires_at',
    ];

    public static function find(Provider $provider, string $externalId): ?self
    {
        return self::query()
            ->where('provider_id', $provider->getKey())
            ->where('external_id', $externalId)
            ->first();
    }

    public static function linked(Authenticatable $user): array
    {
        return self::query()->where('user_id', $user->getAuthIdentifier())->pluck('provider_id')->all();
    }

    public static function unlink(Authenticatable $user, Provider $provider): void
    {
        self::query()
            ->where([
                'user_id' => $user->getAuthIdentifier(),
                'provider_id' => $provider->getKey(),
            ])
            ->delete();
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
