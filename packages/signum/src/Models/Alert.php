<?php

namespace PHPinnacle\Signum\Models;

use Carbon\CarbonImmutable;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use PHPinnacle\Signum\Enums\Level;

/**
 * @property string $id
 * @property string $target_type
 * @property string $target_id
 * @property Level $level
 * @property string $title
 * @property string|null $message
 * @property int|string|null $created_by
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
class Alert extends Model implements HasColor, HasDescription, HasIcon, HasLabel
{
    use HasUuids;

    public $timestamps = true;

    protected $table = 'alerts';

    protected $attributes = [
        'level' => Level::Info->value,
    ];

    protected $casts = [
        'level' => Level::class,
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    protected $fillable = [
        'target_type',
        'target_id',
        'level',
        'title',
        'message',
    ];

    /**
     * @param array<string, mixed> $data
     */
    public static function create(Model $record, array $data): self
    {
        $self = new self($data);
        $self->target_type = $record->getMorphClass();
        $self->setAttribute('target_id', $record->getKey());
        $self->save();

        return $self;
    }

    /**
     * @return Collection<int, self>
     */
    public static function list(?Model $model): Collection
    {
        if ($model === null) {
            return new Collection;
        }

        return self::query()
            ->where([
                'target_type' => $model->getMorphClass(),
                'target_id' => $model->getKey(),
            ])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getColor(): string|array|null
    {
        return $this->level->getColor();
    }

    public function getConnectionName(): ?string
    {
        /** @var ?string */
        return config('phpinnacle-signum.connection');
    }

    public function getDescription(): ?string
    {
        return $this->message;
    }

    public function getIcon(): ?string
    {
        return $this->level->getIcon();
    }

    public function getLabel(): ?string
    {
        return $this->title;
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function booted(): void
    {
        static::creating(function (Alert $record) {
            $record->created_by = Auth::id();
        });
    }
}
