<?php

namespace PHPinnacle\Tags\Models;

use Carbon\CarbonImmutable;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PHPinnacle\Palette\Color;

/**
 * @property string $id
 * @property string $name
 * @property string $type
 * @property string $color
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
class Tag extends Model implements HasColor, HasLabel
{
    use HasUuids;

    public $timestamps = true;

    protected $table = 'tags';

    protected $fillable = [
        'name',
        'type',
        'color',
    ];

    public static function clear(Model $record): void
    {
        DB::table('taggables')
            ->leftJoin('tags', 'tags.id', 'taggables.tag_id')
            ->where('tags.type', $record->getMorphClass())
            ->where('taggables.taggable_id', $record->getKey())
            ->delete();
    }

    public static function list(string ...$ids): Collection
    {
        return $ids !== []
            ? self::query()->whereKey($ids)->pluck('name', 'id')
            : self::query()->pluck('name', 'id');
    }

    public static function manage(Collection|Model $models, array $tags, bool $drop = false): void
    {
        if ($models instanceof Model) {
            $models = collect([$models]);
        }

        if ($tags === [] || $models->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($models, $tags, $drop) {
            $models
                ->groupBy(fn (Model $record) => $record->getMorphClass())
                ->each(function (Collection $records, string $type) use ($tags, $drop) {
                    $definedTags = self::define($type, $tags);

                    if ($drop) {
                        self::manageDrop($records, $definedTags);
                    } else {
                        self::manageSync($records, $definedTags);
                    }
                });
        });
    }

    public static function retrieve(Model $record): Collection
    {
        return DB::table('taggables')
            ->select(['tags.name'])
            ->leftJoin('tags', 'tags.id', 'taggables.tag_id')
            ->where('tags.type', $record->getMorphClass())
            ->where('taggables.taggable_id', $record->getKey())
            ->pluck('name');
    }

    public static function select(string $type): Collection
    {
        return self::query()->where('type', $type)->pluck('name', 'id');
    }

    protected static function booted(): void
    {
        self::creating(function (self $record) {
            $record->color ??= Color::random();
        });
    }

    private static function define(string $type, array $tags): array
    {
        $tags = array_unique(array_filter(array_map('trim', $tags)));

        return array_map(fn ($tag) => self::query()
            ->firstOrCreate([
                'name' => $tag,
                'type' => $type,
            ], [
                'color' => Color::random(),
            ])
            ->getKey(), $tags);
    }

    private static function doSyncQuery(array $tags, string $table, string $key, array $ids): void
    {
        $ids = array_values($ids);
        $tags = implode(',', array_map(fn ($id) => "'{$id}'::uuid", $tags));
        $join = DB::table($table)->select(['id' => $key]);

        if ($ids !== []) {
            $join->whereIn($key, $ids);
        }

        DB::statement(sprintf("
            WITH tag_array AS (
                SELECT UNNEST(ARRAY[%s]) AS tag_id
            )
            INSERT INTO taggables (tag_id, taggable_id)
            SELECT
                tag_array.tag_id,
                source.id
            FROM tag_array
            CROSS JOIN ({$join->toSql()}) AS source
            ON CONFLICT DO NOTHING;
        ", $tags), $ids);
    }

    private static function manageDrop(Collection|string $taggables, array $tags): void
    {
        DB::table('taggables')
            ->whereIn('tag_id', $tags)
            ->whereIn('taggable_id', $taggables->map(fn (Model $record) => $record->getKey())->all())
            ->delete();
    }

    private static function manageSync(Collection $taggables, array $tags): void
    {
        $taggables
            ->groupBy(fn (Model $record) => implode(':', [
                $record->getTable(),
                $record->getKeyName(),
            ]))
            ->each(function (Collection $records, string $definition) use ($tags) {
                [$table, $key] = explode(':', $definition);

                $records
                    ->pluck($key)
                    ->chunk(100)
                    ->each(fn (Collection $ids) => self::doSyncQuery($tags, $table, $key, $ids->all()));
            });
    }

    public function getColor(): array
    {
        return Color::shades($this->color);
    }

    public function getConnectionName(): ?string
    {
        return config('phpinnacle-tags.connection');
    }

    public function getLabel(): string
    {
        return $this->name;
    }
}
