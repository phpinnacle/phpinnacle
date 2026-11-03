<?php

namespace PHPinnacle\Cerber\Models;

use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PHPinnacle\Cerber\Cerberus;
use PHPinnacle\Cerber\Contracts\Viewer;
use PHPinnacle\Cerber\Enums\LoginType;
use PHPinnacle\Cerber\Enums\Prerequisite;
use PHPinnacle\Cerber\Enums\UserStatus;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Stringable;

/**
 * @phpstan-consistent-constructor
 * @property string $id
 * @property string $tenant_id
 * @property string|null $password
 * @property bool $is_password_temporary
 * @property string|null $remember_token
 * @property string $name
 * @property UserStatus $status
 * @property array<int, string> $panels
 * @property array<string, mixed> $settings
 * @property string|null $avatar
 * @property bool $two_factor_email
 * @property string|null $two_factor_secret
 * @property array<string>|null $two_factor_recovery_codes
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Tenant $tenant
 * @property-read Collection<int, Permission> $permissions
 * @property-read Collection<int, Role> $roles
 * @property-read Collection<int, Login> $logins
 */
class User extends Authenticatable implements HasLabel, Viewer, JWTSubject
{
    use HasUuids;
    use Notifiable;

    public $timestamps = true;

    protected $table = 'users';

    protected $attributes = ['two_factor_email' => false, 'is_password_temporary' => false];

    protected $casts = [
        'password' => 'hashed',
        'is_password_temporary' => 'boolean',
        'status' => UserStatus::class,
        'panels' => 'array',
        'settings' => 'array',
        'two_factor_email' => 'boolean',
        'two_factor_secret' => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted:array',
    ];

    protected $fillable = [
        'name',
        'password',
        'is_password_temporary',
        'status',
        'panels',
        'settings',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public static function findByLogin(LoginType $type, string $value): ?static
    {
        return static::query()
            ->whereHas('logins', fn (Builder $query) => $query
                ->where('type', $type)
                ->whereNull('provider_id')
                ->where('value', $type->canonical($value)))
            ->first();
    }

    /** @param list<string> $panels */
    public static function register(string|Stringable $name, array $panels = [], ?string $id = null): static
    {
        $self = new static;
        $self->id = $id;
        $self->status = UserStatus::Active;
        $self->panels = $panels;
        $self->name = (string) $name;

        return $self;
    }

    public static function current(): static
    {
        /** @var static */
        return Filament::auth()->user();
    }

    public function defaultLogin(LoginType $type): ?Login
    {
        return $this->logins()->where('type', $type)->where('is_default', true)->first();
    }

    /** @return list<Prerequisite> */
    public function prerequisites(): array
    {
        return $this->is_password_temporary ? [Prerequisite::Password] : [];
    }

    public function getEmailForPasswordReset(): ?string
    {
        return $this->defaultLogin(LoginType::Email)?->value;
    }

    public function routeNotificationForMail(): ?string
    {
        return $this->defaultLogin(LoginType::Email)?->value;
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /** @return array<string, mixed> */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /** @return HasMany<Login, $this> */
    public function logins(): HasMany
    {
        return $this->hasMany(Login::class);
    }

    public function able(string $ability, Tenant|string|null $tenant = null): bool
    {
        $tenant = $tenant instanceof Tenant ? $tenant->id : $tenant;

        return $this->loadPermissions($tenant ?? Tenant::DEFAULT)->contains($ability);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->status->canAccess();
    }

    public function canAccessTenant(Eloquent $tenant): bool
    {
        return $this->getTenants(Filament::getCurrentOrDefaultPanel())->contains($tenant);
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->name;
    }

    /** @return list<string>|null */
    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->two_factor_recovery_codes;
    }

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->two_factor_secret;
    }

    public function getConnectionName(): ?string
    {
        return config('phpinnacle-cerber.connection', parent::getConnectionName());
    }

    public function getDefaultTenant(Panel $panel): ?Eloquent
    {
        return Filament::getTenant() ?? $this->tenant;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if ($this->avatar !== null && $this->avatar !== '') {
            return asset(sprintf('storage/%s', $this->avatar));
        }

        $name = str($this->getFilamentName())
            ->trim()
            ->explode(' ')
            ->take(2)
            ->map(fn (string $segment) => filled($segment) ? mb_substr($segment, start: 0, length: 1) : '')
            ->join('');

        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=black&color=fff';
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->name;
    }

    /**
     * @return Collection<int, Tenant>
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->roles->map(fn (Role $role) => $role->tenant)->uniqueStrict('id');
    }

    public function grant(Permission|Role|string ...$permissions): self
    {
        [$roles, $permissions] = collect($permissions)->partition(fn ($item) => $item instanceof Role)->all();

        foreach ($roles as $role) {
            $this->roles()->attach($role);
        }

        foreach ($permissions as $permission) {
            $this->permissions()->attach(Permission::filter($permission, register: true));
        }

        return $this;
    }

    public function hasEmailAuthentication(): bool
    {
        return $this->two_factor_email;
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'users_permissions');
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'users_roles');
    }

    /** @param list<string>|null $codes */
    public function saveAppAuthenticationRecoveryCodes(?array $codes): void
    {
        $this->two_factor_recovery_codes = $codes;
        $this->save();
    }

    public function saveAppAuthenticationSecret(#[\SensitiveParameter] ?string $secret): void
    {
        $this->two_factor_secret = $secret;
        $this->save();
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function toggleEmailAuthentication(bool $condition): void
    {
        $this->two_factor_email = $condition;
        $this->save();
    }

    public function toggleArchived(): void
    {
        $this->status = $this->status === UserStatus::Active ? UserStatus::Archived : UserStatus::Active;
        $this->save();
    }

    protected static function booted(): void
    {
        self::saving(function (self $record) {
            if ($record->exists && $record->isDirty('password')) {
                $record->remember_token = Str::random(60);
            }
        });

        self::creating(function (self $record) {
            $record->tenant_id ??= Cerberus::tenant();

            if ($record->panels === []) {
                $record->panels = array_filter([Filament::getCurrentOrDefaultPanel()?->getId()]);
            }
        });
    }

    /**
     * @return Collection<int, string>
     */
    private function loadPermissions(string $tenant): Collection
    {
        return once(
            fn () => Permission::query()
                ->whereHas('roles', function (Builder $query) use ($tenant) {
                    $query
                        ->where('tenant_id', $tenant)
                        ->whereRelation('users', 'id', $this->id);
                })
                ->pluck('permissions.name'),
        );
    }
}
