<?php

namespace PHPinnacle\Cerber\Models;

use Carbon\CarbonImmutable;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Uri;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use PHPinnacle\Cerber\Contracts\SocialAccountContext;
use PHPinnacle\Cerber\Enums\LoginType;
use PHPinnacle\Cerber\Exceptions\OAuthException;

/**
 * @property string $id
 * @property string $type
 * @property string $name
 * @property array<string, mixed> $config
 * @property string $context
 * @property bool $is_active
 * @property int $sort
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Collection<int, Login> $logins
 */
class Provider extends Model implements HasLabel
{
    use HasUuids;

    protected $table = 'oauth_providers';

    protected $attributes = [
        'is_active' => false,
    ];

    protected $casts = [
        'config' => 'encrypted:array',
        'is_active' => 'bool',
    ];

    protected $fillable = [
        'name',
        'type',
        'config',
        'is_active',
        'context',
        'sort',
    ];

    /**
     * @return Builder<self>
     */
    public static function active(): Builder
    {
        return self::query()
            ->where('is_active', true)
            ->orderBy('sort');
    }

    /**
     * @return Collection<int, self>
     */
    public static function valid(SocialAccountContext $context): Collection
    {
        return self::active()
            ->where('context', $context->name())
            ->get()
            ->filter(fn (self $provider) => $provider->isConfigValid());
    }

    public static function booted(): void
    {
        self::deleted(fn (self $record) => $record->logins()->get()->each->delete());

        self::creating(function (self $record) {
            reset_sort($record);
        });
    }

    public function delete(): ?bool
    {
        return $this->getConnection()->transaction(parent::delete(...));
    }

    public function getLabel(): string
    {
        return $this->name;
    }

    public function toggleActive(): void
    {
        $this->is_active = !$this->is_active;
        $this->save();
    }

    public function isConfigValid(): bool
    {
        return filled($this->config['client_id'] ?? null) && filled($this->config['client_secret'] ?? null);
    }

    public function isAvailableTo(SocialAccountContext $context): bool
    {
        return $this->is_active && $this->context === $context->name() && $this->isConfigValid();
    }

    public function link(User $user, SocialiteUser $identity): Login
    {
        $email = $identity->getEmail();

        if (Validator::make(['email' => $email], ['email' => ['nullable', 'string', 'email', 'max:255']])->fails()) {
            throw new OAuthException('invalid_profile', 'The OAuth provider returned an invalid email address.');
        }

        return $this->getConnection()->transaction(function () use ($user, $identity, $email) {
            $user->newQuery()->whereKey($user->id)->lockForUpdate()->first();
            $login = $this->logins()->whereBelongsTo($user, 'user')->first();

            if ($login !== null && $login->value !== $identity->getId()) {
                $login->delete();
                $login = null;
            }

            $login ??= new Login;
            $login->user()->associate($user);
            $login->type = LoginType::OAuth;
            $login->provider_id = $this->id;
            $login->value = $identity->getId();
            $login->metadata = [
                ...($login->metadata ?? []),
                'email' => $identity->getEmail(),
                'name' => $identity->getName(),
                'nickname' => $identity->getNickname(),
                'avatar' => $identity->getAvatar(),
            ];
            $login->save();

            if ($email !== null && Login::query()
                ->whereNull('provider_id')
                ->where('value', LoginType::Email->canonical($email))
                ->doesntExist()) {
                Login::register($user, LoginType::Email, $email);
            }

            return $login;
        });
    }

    public function unlink(User $user): void
    {
        $this->logins()->whereBelongsTo($user, 'user')->first()?->delete();
    }

    public function frontendCallbackUrl(): string
    {
        return (string) Uri::of(config('phpinnacle-cerber.api.callback_url'))
            ->withQuery(['context' => $this->context, 'provider' => $this->id]);
    }

    /**
     * @return HasMany<Login, $this>
     */
    public function logins(): HasMany
    {
        return $this->hasMany(Login::class, 'provider_id')->where('type', LoginType::OAuth);
    }
}
