<?php

namespace PHPinnacle\Cerber\Models;

use Carbon\CarbonImmutable;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use PHPinnacle\Cerber\Enums\TenantStatus;

/**
 * @property string $id
 * @property string $domain
 * @property string|null $name
 * @property string|null $logo
 * @property TenantStatus $status
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Collection<User> $users
 * @property-read Collection<Role> $roles
 */
class Tenant extends Model implements HasAvatar, HasCurrentTenantLabel, HasName
{
    use HasUuids;

    public const string DEFAULT = '00000000-0000-0000-0000-000000000000';

    public $timestamps = true;

    protected $table = 'tenants';

    protected $attributes = [
        'status' => TenantStatus::Active,
    ];

    protected $casts = [
        'status' => TenantStatus::class,
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    protected $fillable = [
        'domain',
        'name',
        'logo',
        'status',
    ];

    public static function resolve(Panel $panel): ?self
    {
        $pattern = str_replace('\{tenant\:domain\}', '(\w+)', preg_quote($panel->getTenantDomain(), '/'));

        preg_match(sprintf('/%s/', $pattern), request()->getHost(), $matches);

        return self::query()->where('domain', $matches[1])->first();
    }

    public static function get(string $id, string $key): self
    {
        return self::query()->where($key, $id)->sole();
    }

    public static function locate(string $domain, ?string $name = null): self
    {
        return self::query()
            ->firstOrCreate([
                'domain' => $domain,
            ], [
                'name' => $name,
                'status' => TenantStatus::Active->value,
            ]);
    }

    public static function default(string $domain): self
    {
        $self = new self;
        $self->id = self::DEFAULT;
        $self->domain = $domain;
        $self->status = TenantStatus::Active;
        $self->save();

        return $self;
    }

    public function getFilamentName(): string
    {
        return $this->name ?? $this->domain;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->logo;
    }

    public function getCurrentTenantLabel(): string
    {
        return '';
    }

    public function getRouteKeyName(): string
    {
        return 'domain';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }
}
