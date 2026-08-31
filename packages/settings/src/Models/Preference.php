<?php

namespace PHPinnacle\Settings\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @property string $id
 * @property string $user_id
 * @property string $key
 * @property string $group
 * @property mixed $value
 */
class Preference extends Model
{
    use HasUuids;

    public $timestamps = true;

    protected $table = 'preferences';

    protected $fillable = [
        'user_id',
        'key',
        'group',
        'value',
    ];

    public static function get(Authenticatable $record, string $group, string $key): mixed
    {
        return self::query()
            ->where('user_id', $record->getAuthIdentifier())
            ->where('group', $group)
            ->where('key', $key)
            ->first()
            ?->value;
    }

    public static function retrieve(Authenticatable $record): array
    {
        return self::query()
            ->where('user_id', $record->getAuthIdentifier())
            ->get()
            ->groupBy('group')
            ->map(fn (Collection $items) => $items->pluck('value', 'key')->all())
            ->all();
    }

    public static function store(Authenticatable $record, array $values): void
    {
        DB::transaction(function () use ($record, $values) {
            $records = [];

            foreach ($values as $group => $items) {
                foreach ($items as $key => $value) {
                    $records[] = [
                        'user_id' => $record->getAuthIdentifier(),
                        'group' => $group,
                        'key' => $key,
                        'value' => json_encode($value, JSON_UNESCAPED_UNICODE),
                    ];
                }
            }

            self::query()->upsert($records, ['user_id', 'group', 'key'], ['value']);
        });
    }

    public function value(): Attribute
    {
        return new Attribute(
            get: fn (mixed $value) => $value !== null ? json_decode($value, associative: true) : $value,
            set: fn (mixed $value) => json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }
}
