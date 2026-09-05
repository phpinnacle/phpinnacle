<?php

namespace PHPinnacle\Cerber\Models;

use Carbon\CarbonImmutable;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PHPinnacle\Cerber\Cerberus;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $description
 * @property bool $is_active
 * @property bool $is_system
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Tenant $tenant
 * @property-read Collection<int, Permission> $permissions
 * @property-read Collection<int, User> $users
 */
class Role extends Model implements HasLabel
{
    use HasUuids;

    public $timestamps = true;

    protected $table = 'roles';

    protected $attributes = [
        'is_active' => true,
        'is_system' => false,
    ];

    protected $casts = [
        'is_active' => 'bool',
        'is_system' => 'bool',
    ];

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'is_system',
    ];

    /**
     * @return Builder<self>
     */
    public static function active(): Builder
    {
        return self::query()
            ->where([
                'is_active' => true,
            ]);
    }

    public static function find(string $id): ?self
    {
        return self::query()->find($id);
    }

    /**
     * @return Collection<string, string>
     */
    public static function list(): Collection
    {
        return self::active()->pluck('name', 'id');
    }

    /**
     * @param array<array-key, Permission|string> $permissions
     */
    public static function register(Tenant $tenant, string $name, array $permissions = []): self
    {
        /** @var Role $self */
        $self = self::query()
            ->firstOrCreate([
                'tenant_id' => $tenant->id,
                'name' => $name,
            ]);

        $self->grant(...$permissions);

        return $self;
    }

    /**
     * @param array<array-key, array<array-key, Permission|string>> $permissions
     */
    public static function system(Tenant $tenant, string $name, array $permissions = []): self
    {
        $self = new self;
        $self->name = $name;
        $self->tenant_id = $tenant->id;
        $self->is_system = true;
        $self->save();
        $self->grant(...collect($permissions)->collapse()->all());

        return $self;
    }

    public function able(string|Permission $permission): bool
    {
        $permission = Permission::filter($permission);

        return $permission !== null && $this->permissions->contains($permission->getKeyName(), $permission->getKey());
    }

    public function getLabel(): ?string
    {
        return $this->name;
    }

    public function grant(Permission|string ...$permissions): self
    {
        DB::transaction(function () use ($permissions) {
            $exists = $this->permissions->pluck('id')->all();
            $actual = [];
            $relation = $this->permissions();

            foreach ($permissions as $permission) {
                $permission = Permission::filter($permission, register: true);

                if ($permission === null) {
                    continue;
                }

                $actual[] = $permission->getKey();

                if (!in_array($permission->getKey(), $exists, true)) {
                    $relation->attach($permission);
                }
            }

            $relation->detach(array_diff($exists, $actual));
        });

        return $this;
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'roles_permissions');
    }

    public function toggleActive(): void
    {
        if ($this->is_system) {
            return;
        }

        $this->is_active = !$this->is_active;
        $this->save();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_roles');
    }

    protected static function booted(): void
    {
        self::creating(function (self $record) {
            $record->tenant_id ??= Cerberus::tenant();
        });

        self::saving(function (self $record) {
            $record->description ??= '';
        });
    }
}
