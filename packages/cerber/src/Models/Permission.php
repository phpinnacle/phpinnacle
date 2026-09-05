<?php

namespace PHPinnacle\Cerber\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $name
 * @property string $description
 * @property bool $is_active
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Collection<int, Role> $roles
 */
class Permission extends Model
{
    use HasUuids;

    public $timestamps = true;

    protected $table = 'permissions';

    protected $attributes = [
        'is_active' => true,
    ];

    protected $casts = [
        'is_active' => 'bool',
    ];

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    public static function findById(string $id): ?self
    {
        return self::query()->find($id);
    }

    public static function findByName(string $name): ?self
    {
        return self::query()->where('name', $name)->first();
    }

    public static function register(string $name): self
    {
        return self::query()
            ->firstOrCreate([
                'name' => $name,
            ]);
    }

    public static function filter(mixed $value, bool $register = false): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (Str::isUuid($value)) {
            return self::findById($value);
        }

        return is_string($value) ? self::findByName($value) ?? ($register ? self::register($value) : null) : null;
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'roles_permissions');
    }
}
