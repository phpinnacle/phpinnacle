<?php

namespace PHPinnacle\Cerber\Models;

use Carbon\CarbonImmutable;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use PHPinnacle\Cerber\Contracts\SocialAccountContext;
use PHPinnacle\Cerber\Enums\LoginType;

/**
 * @property string $id
 * @property string $user_id
 * @property LoginType $type
 * @property string|null $provider_id
 * @property string $value
 * @property bool $is_default
 * @property CarbonImmutable|null $verified_at
 * @property array<string, mixed>|null $metadata
 * @property-read User $user
 * @property-read Provider|null $provider
 */
class Login extends Model
{
    use HasUuids;
    use Notifiable;

    protected $table = 'logins';

    protected $attributes = ['is_default' => false];

    protected $fillable = ['type', 'value', 'metadata'];

    protected $casts = [
        'type' => LoginType::class,
        'is_default' => 'boolean',
        'verified_at' => 'immutable_datetime',
        'metadata' => 'array',
    ];

    public static function lookup(LoginType $type, string $value, ?string $providerId = null): ?self
    {
        return self::query()
            ->where('type', $type)
            ->where('provider_id', $providerId)
            ->where('value', $type->canonical($value))
            ->first();
    }

    public static function register(User $user, LoginType $type, string $value): self
    {
        return $user->logins()->create(['type' => $type, 'value' => $value]);
    }

    /** @param array<string, mixed> $options */
    public function save(array $options = []): bool
    {
        if ($this->exists && !$this->isDirty('is_default')) {
            return parent::save($options);
        }

        return $this->getConnection()->transaction(function () use ($options) {
            $this->user()->lockForUpdate()->first();

            if (!$this->exists) {
                $this->is_default = !$this->user->logins()->where('type', $this->type)->exists();
            }

            $saved = parent::save($options);

            if ($saved && $this->is_default) {
                $this->user
                    ->logins()
                    ->where('type', $this->type)
                    ->whereKeyNot($this->id)
                    ->update(['is_default' => false]);
            }

            return $saved;
        });
    }

    public function delete(): ?bool
    {
        return $this->getConnection()->transaction(function () {
            $this->user()->lockForUpdate()->first();
            $wasDefault = $this->newQuery()->whereKey($this->id)->value('is_default');
            $deleted = parent::delete();

            if ($deleted && $wasDefault) {
                $next = $this->user
                    ->logins()
                    ->where('type', $this->type)
                    ->oldest()
                    ->orderBy('id')
                    ->first();
                if ($next !== null) {
                    $next->is_default = true;
                    $next->save();
                }
            }

            return $deleted;
        });
    }

    /** @return list<string> */
    public static function linked(SocialAccountContext $context, User $user): array
    {
        return $user
            ->logins()
            ->where('type', LoginType::OAuth)
            ->whereHas('provider', fn ($query) => $query->where('context', $context->name()))
            ->pluck('provider_id')
            ->all();
    }

    public function requiresVerification(): bool
    {
        return $this->type === LoginType::Email && $this->verified_at === null;
    }

    public function verificationHash(): string
    {
        return hash('sha256', $this->value);
    }

    public function matchesVerificationHash(string $hash): bool
    {
        return $this->type === LoginType::Email && hash_equals($this->verificationHash(), $hash);
    }

    public function isAvailable(): bool
    {
        return (
            $this->user->status->canAccess()
            && !$this->requiresVerification()
            && ($this->type !== LoginType::OAuth || ($this->provider->is_active ?? false))
        );
    }

    public function markVerified(): void
    {
        $this->verified_at = CarbonImmutable::now();
        $this->save();
    }

    public function dropVerification(): void
    {
        $this->verified_at = null;
        $this->save();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAvailable() && $this->user->canAccessPanel($panel);
    }

    public function routeNotificationForMail(): string
    {
        return $this->value;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        /** @var class-string<User> $model */
        $model = config('phpinnacle-cerber.user_model');

        return $this->belongsTo($model, 'user_id');
    }

    /** @return BelongsTo<Provider, $this> */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    protected static function booted(): void
    {
        self::saving(function (self $login) {
            $login->value = $login->type->canonical($login->value);

            if ($login->exists && $login->isDirty(['type', 'provider_id', 'value'])) {
                $login->verified_at = null;
            }
        });
    }
}
