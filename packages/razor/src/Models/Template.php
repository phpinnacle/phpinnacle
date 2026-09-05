<?php

namespace PHPinnacle\Razor\Models;

use Filament\Facades\Filament;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use PHPinnacle\Razor\Enums\Engine;
use PHPinnacle\Razor\Enums\Format;
use PHPinnacle\Sequentia\Sequence;

/**
 * @property string $id
 * @property string $parent_id
 * @property string $tenant_id
 * @property string $name
 * @property string $numeration
 * @property Format $format
 * @property Engine $engine
 * @property string $section
 * @property string|array $content
 * @property array $context
 * @property int $version
 * @property string $is_active
 * @property string $is_default
 * @property string $is_history
 * @property string $created_by
 * @property string $created_at
 * @property string $updated_at
 * @property-read Template|null $parent
 * @property-read Collection<Template> $history
 */
class Template extends Model implements HasLabel
{
    use HasUuids;

    protected $table = 'templates';

    protected $attributes = [
        'format' => Format::HTML->value,
        'engine' => Engine::Twig->value,
        'context' => '{}',
        'version' => 1,
        'is_active' => true,
        'is_default' => false,
        'is_history' => false,
    ];

    protected $casts = [
        'format' => Format::class,
        'engine' => Engine::class,
        'context' => 'array',
        'is_active' => 'bool',
    ];

    protected $fillable = [
        'name',
        'numeration',
        'format',
        'engine',
        'section',
        'content',
        'context',
        'version',
        'is_active',
        'is_default',
        'is_history',
        'created_at',
        'updated_at',
    ];

    public static function active(): Builder
    {
        return self::query()
            ->where([
                'is_active' => true,
                'is_history' => false,
            ]);
    }

    public static function get(string $id): self
    {
        return self::query()->findOrFail($id);
    }

    public function content(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => $this->format->decode($value),
            set: fn (mixed $value) => $this->format->encode($value),
        );
    }

    public function getLabel(): ?string
    {
        return $this->name;
    }

    public function history(): HasMany
    {
        return $this
            ->hasMany(self::class, 'parent_id')
            ->where('is_history', true)
            ->latest('version');
    }

    public function makeNumber(Model $holder): ?string
    {
        if ($this->numeration === null) {
            return null;
        }

        return Sequence::create($this->numeration, Document::class, [
            'holder_type' => $holder->getMorphClass(),
            'holder_id' => $holder->getKey(),
        ])
            ->forTenant(Filament::getTenant()?->getKey())
            ->get();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    public function toggleActive(): void
    {
        if ($this->is_default || $this->is_history) {
            return;
        }

        $this->is_active = !$this->is_active;
        $this->save();
    }

    public function toggleDefault(): void
    {
        if (!$this->is_active || $this->is_history) {
            return;
        }

        $this->is_default = !$this->is_default;
        $this->save();
    }

    protected static function booted(): void
    {
        self::saving(function (self $record) {
            if ($record->is_default) {
                self::query()
                    ->where('section', $record->section)
                    ->where('id', '!=', $record->id)
                    ->update(['is_default' => false]);
            }
        });

        self::creating(function (self $record) {
            $record->parent_id ??= $record->id;
            $record->created_by = auth()->id();
        });

        self::updating(function (self $record) {
            if (!$record->isDirty('content')) {
                return;
            }

            $record->content = html_entity_decode($record->content);
            $record->version++;

            $previous = new self($record->getOriginal());
            $previous->parent_id = $record->id;
            $previous->is_history = true;
            $previous->is_default = false;
            $previous->save();
        });
    }
}
