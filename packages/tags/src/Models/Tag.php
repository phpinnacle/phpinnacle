<?php

namespace PHPinnacle\Tags\Models;

use Carbon\CarbonImmutable;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
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
        new self()
            ->getConnection()
            ->table('taggables')
            ->leftJoin('tags', 'tags.id', 'taggables.tag_id')
            ->where('tags.type', $record->getMorphClass())
            ->where('taggables.taggable_id', $record->getKey())
            ->delete();
    }

    /**
     * @return Collection<string, string>
     */
    public static function list(string ...$ids): Collection
    {
        // @mago-expect lint:inline-variable-return
        /** @var Collection<string, string> $tags */
        $tags = $ids !== []
            ? self::query()->whereKey($ids)->pluck('name', 'id')
            : self::query()->pluck('name', 'id');

        return $tags;
    }

    /**
     * @param Collection<array-key, Model>|Model $models
     * @param array<array-key, string> $tags
     */
    public static function manage(Collection|Model $models, array $tags, bool $drop = false): void
    {
        if ($models instanceof Model) {
            $models = collect([$models]);
        }

        if ($tags === [] || $models->isEmpty()) {
            return;
        }

        new self()
            ->getConnection()
            ->transaction(function () use ($models, $tags, $drop) {
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

    /**
     * @return Collection<int, string>
     */
    public static function retrieve(Model $record): Collection
    {
        // @mago-expect lint:inline-variable-return
        /** @var Collection<int, string> $tags */
        $tags = new self()
            ->getConnection()
            ->table('taggables')
            ->select(['tags.name'])
            ->leftJoin('tags', 'tags.id', 'taggables.tag_id')
            ->where('tags.type', $record->getMorphClass())
            ->where('taggables.taggable_id', $record->getKey())
            ->pluck('name');

        return $tags;
    }

    /**
     * @return Collection<string, string>
     */
    public static function select(string $type): Collection
    {
        // @mago-expect lint:inline-variable-return
        /** @var Collection<string, string> $tags */
        $tags = self::query()->where('type', $type)->pluck('name', 'id');

        return $tags;
    }

    public function getColor(): array
    {
        return Color::shades($this->color);
    }

    public function getConnectionName(): ?string
    {
        return Config::get('phpinnacle-tags.connection') === null
            ? null
            : Config::string('phpinnacle-tags.connection');
    }

    public function getLabel(): string
    {
        return $this->name;
    }

    protected static function booted(): void
    {
        self::creating(function (self $record) {
            $record->color ??= Color::random();
        });
    }

    /**
     * @param array<array-key, string> $tags
     * @return array<array-key, string>
     */
    private static function define(string $type, array $tags): array
    {
        $tags = array_unique(array_filter(array_map('trim', $tags)));

        return array_map(
            fn ($tag) => self::query()
                ->firstOrCreate([
                    'name' => $tag,
                    'type' => $type,
                ], [
                    'color' => Color::random(),
                ])
                ->id,
            $tags,
        );
    }

    /**
     * @param array<array-key, string> $tags
     * @param array<array-key, int|string> $ids
     */
    private static function doSyncQuery(array $tags, array $ids): void
    {
        $rows = [];

        foreach ($tags as $tag) {
            foreach ($ids as $id) {
                $rows[] = ['tag_id' => $tag, 'taggable_id' => $id];
            }
        }

        new self()
            ->getConnection()
            ->table('taggables')
            ->insertOrIgnore($rows);
    }

    /**
     * @param Collection<array-key, Model> $taggables
     * @param array<array-key, string> $tags
     */
    private static function manageDrop(Collection $taggables, array $tags): void
    {
        new self()
            ->getConnection()
            ->table('taggables')
            ->whereIn('tag_id', $tags)
            ->whereIn('taggable_id', $taggables->map(fn (Model $record) => $record->getKey())->all())
            ->delete();
    }

    /**
     * @param Collection<array-key, Model> $taggables
     * @param array<array-key, string> $tags
     */
    private static function manageSync(Collection $taggables, array $tags): void
    {
        $taggables
            ->groupBy(fn (Model $record) => implode(':', [
                $record->getConnectionName(),
                $record->getTable(),
                $record->getKeyName(),
            ]))
            ->each(function (Collection $records) use ($tags) {
                /** @var Model $record */
                $record = $records->first();
                $key = $record->getKeyName();

                $records
                    ->pluck($key)
                    ->chunk(100)
                    ->each(function (Collection $ids) use ($record, $key, $tags) {
                        /** @var array<array-key, int|string> $existingIds */
                        $existingIds = $record
                            ->getConnection()
                            ->table($record->getTable())
                            ->whereIn($key, $ids->all())
                            ->pluck($key)
                            ->all();

                        self::doSyncQuery($tags, $existingIds);
                    });
            });
    }
}
