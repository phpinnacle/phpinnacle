<?php

namespace PHPinnacle\Razor\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property string|null $parent_id
 * @property string $holder_type
 * @property string $holder_id
 * @property string $entity_type
 * @property string $entity_id
 * @property string $template_id
 * @property string $number
 * @property string $content
 * @property array<string, mixed> $context
 * @property string $created_by
 * @property CarbonImmutable $issued_at
 * @property CarbonImmutable $signed_at
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Document|null $parent
 * @property-read Template $template
 */
class Document extends Model
{
    use HasUuids;

    public const string ROOT = '00000000-0000-0000-0000-000000000000';

    protected $table = 'documents';

    protected $casts = [
        'context' => 'array',
        'issued_at' => 'immutable_date',
        'signed_at' => 'immutable_date',
        'expires_at' => 'immutable_date',
    ];

    protected $fillable = [
        'parent_id',
        'holder_type',
        'holder_id',
        'entity_type',
        'entity_id',
        'template_id',
        'number',
        'content',
        'context',
        'issued_at',
        'signed_at',
        'expires_at',
        'created_at',
        'updated_at',
    ];

    public function __construct(array $attributes = [])
    {
        $now = $this->freshTimestamp();

        parent::__construct(array_replace([
            'created_at' => $now,
            'updated_at' => $now,
        ], $attributes));
    }

    public static function get(string $id): self
    {
        return self::query()->findOrFail($id);
    }

    /**
     * @return array<string, string>
     */
    public static function select(Model $holder): array
    {
        return self::query()
            ->where([
                'holder_type' => $holder->getMorphClass(),
                'holder_id' => $holder->getKey(),
            ])
            ->pluck('number', 'id')
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return array_merge($this->context, [
            '_document' => [
                'number' => $this->number,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
                'issued_at' => $this->issued_at,
                'signed_at' => $this->signed_at,
                'expires_at' => $this->expires_at,
            ],
            '_parent' => $this->parent?->context() ?? [],
        ]);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function holder(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return BelongsTo<Template, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    protected static function booted(): void
    {
        self::creating(function (self $record) {
            $record->created_by = auth()->id();
        });

        self::deleted(function (self $record) {
            self::query()
                ->where(['parent_id' => $record->id])
                ->update([
                    'parent_id' => null,
                ]);
        });
    }
}
