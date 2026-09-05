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
use PHPinnacle\Cerber\Cerberus;
use PHPinnacle\Cerber\Contracts\Viewer;
use PHPinnacle\Cerber\Enums\UserStatus;
use Stringable;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property UserStatus $status
 * @property array $panels
 * @property array $settings
 * @property string|null $avatar
 * @property bool $two_factor_email
 * @property string|null $two_factor_secret
 * @property array|null $two_factor_recovery_codes
 * @property CarbonImmutable $password_changed_at
 * @property CarbonImmutable|null $email_verified_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Tenant $tenant
 * @property-read Collection<Permission> $permissions
 * @property-read Collection<Role> $roles
 */
class User extends Authenticatable implements HasLabel, Viewer
{
    use HasUuids;
    use Notifiable;

    public $timestamps = true;

    protected $table = 'users';

    protected $casts = [
        'status' => UserStatus::class,
        'password' => 'hashed',
        'panels' => 'array',
        'settings' => 'array',
        'password_changed_at' => 'immutable_datetime',
        'email_verified_at' => 'immutable_datetime',
        'two_factor_email' => 'boolean',
        'two_factor_secret' => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted:array',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
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

    public static function find(string $login): ?self
    {
        return self::query()->where('email', $login)->first();
    }

    public static function register(Login $login, string|Stringable $name, ?string $id = null): self
    {
        $self = new self;
        $self->id = $id;
        $self->status = UserStatus::Active;
        $self->email = $login->username;
        $self->password = $login->password;
        $self->panels = $login->panels;
        $self->name = (string) $name;

        if ($login->verified) {
            $self->email_verified_at = CarbonImmutable::now();
        }

        return $self;
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

    public function dropVerification(): void
    {
        $this->email_verified_at = null;
        $this->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

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

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'users_permissions');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'users_roles');
    }

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

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function toggleEmailAuthentication(bool $condition): void
    {
        $this->two_factor_email = $condition;
        $this->save();
    }

    protected static function booted(): void
    {
        self::creating(function (self $record) {
            $record->tenant_id ??= Cerberus::tenant();

            if ($record->panels === []) {
                $record->panels = array_filter([Filament::getCurrentOrDefaultPanel()?->getId()]);
            }
        });

        self::updating(function (self $record) {
            if ($record->isDirty('email')) {
                $record->email_verified_at = null;
            }
        });

        self::saving(function (self $record) {
            if ($record->isDirty('password')) {
                $record->password_changed_at = CarbonImmutable::now();
            }
        });
    }

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
