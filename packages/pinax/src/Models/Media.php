<?php

namespace PHPinnacle\Pinax\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * @property string $id
 * @property string $holder_type
 * @property string $holder_id
 * @property string $name
 * @property string $mime
 * @property string $path
 * @property string $disk
 * @property string $folder
 * @property array<int, string> $marks
 * @property int $size
 * @property int $sort
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Model $holder
 */
class Media extends Model
{
    use HasUuids;

    public $timestamps = true;

    protected $table = 'media';

    protected $attributes = [
        'marks' => '[]',
    ];

    protected $casts = [
        'marks' => 'array',
    ];

    protected $fillable = [
        'holder_type',
        'holder_id',
        'name',
        'mime',
        'path',
        'disk',
        'folder',
        'marks',
        'size',
        'sort',
    ];

    /**
     * @param list<string> $exclude
     */
    public static function clear(Model $record, string $folder, array $exclude = []): void
    {
        $query = self::query()
            ->where([
                'holder_type' => $record->getMorphClass(),
                'holder_id' => $record->getKey(),
                'folder' => $folder,
            ]);

        if ($exclude !== []) {
            $query = $query->whereKeyNot($exclude);
        }

        $query->delete();
    }

    /**
     * @return array{name: string, size: int, type: string, url: string}|null
     */
    public static function display(string $id): ?array
    {
        $record = self::query()->find($id);

        return (
            $record !== null
                ? [
                    'name' => $record->name,
                    'size' => $record->size,
                    'type' => $record->mime,
                    'url' => $record->url(),
                ] : null
        );
    }

    /**
     * @return Collection<int, self>
     */
    public static function fetch(Model $record, string $folder): Collection
    {
        return self::query()
            ->where([
                'holder_type' => $record->getMorphClass(),
                'holder_id' => $record->getKey(),
                'folder' => $folder,
            ])
            ->orderBy('sort')
            ->get();
    }

    /**
     * @template TModel of Model
     * @param TModel $record
     * @return MorphMany<self, TModel>
     */
    public static function folder(Model $record, string $folder): MorphMany
    {
        return $record->morphMany(self::class, 'holder')->withAttributes(['folder' => $folder]);
    }

    /**
     * @template TModel of Model
     * @param TModel $record
     * @return MorphOne<self, TModel>
     */
    public static function one(Model $record, string $folder, string $mark): MorphOne
    {
        return self::folder($record, $folder)
            ->one()
            ->ofMany(
                [
                    'sort' => 'min',
                ],
                fn (Builder $builder) => $builder->whereJsonContains('marks', $mark),
            );
    }

    /**
     * @template TModel of Model
     * @param TModel $record
     * @return MorphOne<self, TModel>
     */
    public static function single(Model $record, string $folder): MorphOne
    {
        return $record->morphOne(self::class, 'holder')->withAttributes(['folder' => $folder]);
    }

    public static function store(
        Model $record,
        TemporaryUploadedFile $file,
        string $disk,
        string $folder,
        string $path,
    ): self {
        $self = new self;
        $self->holder_type = $record->getMorphClass();
        $self->holder_id = $record->getKey();
        $self->name = $file->getClientOriginalName();
        $self->mime = $file->getMimeType();
        $self->size = $file->getSize();
        $self->disk = $disk;
        $self->folder = $folder;
        $self->path = $path;

        return $self;
    }

    public function getConnectionName(): ?string
    {
        return config('phpinnacle-pinax.connection', parent::getConnectionName());
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function holder(): MorphTo
    {
        return $this->morphTo();
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    protected static function booted(): void
    {
        self::creating(function (self $record) {
            $record->marks ??= [];

            reset_sort($record, [
                'holder_type' => $record->holder_type,
                'holder_id' => $record->holder_id,
            ]);
        });
    }
}
